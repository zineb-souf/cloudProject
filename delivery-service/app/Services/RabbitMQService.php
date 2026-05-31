<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class RabbitMQService
{
    private $connection;
    private $channel;
    
    public function __construct()
    {
        try {
            $this->connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'localhost'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest')
            );
            $this->channel = $this->connection->channel();
        } catch (Exception $e) {
            // En cas d'erreur de connexion, on log mais on ne fait pas planter l'app
            \Log::warning('RabbitMQ connection failed: ' . $e->getMessage());
            $this->connection = null;
            $this->channel = null;
        }
    }
    
    public function publishDeliveryStatusChange($deliveryId, $status)
    {
        if (!$this->channel) {
            \Log::warning('RabbitMQ not available, skipping message publish');
            return false;
        }
        
        try {
            // Déclarer la queue
            $this->channel->queue_declare('delivery_status_changes', false, true, false, false);
            
            // Préparer le message
            $messageData = [
                'delivery_id' => $deliveryId,
                'status' => $status,
                'timestamp' => now()->toISOString()
            ];
            
            $message = new AMQPMessage(
                json_encode($messageData),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
            
            // Publier le message
            $this->channel->basic_publish($message, '', 'delivery_status_changes');
            
            \Log::info('RabbitMQ message published', $messageData);
            return true;
            
        } catch (Exception $e) {
            \Log::error('Failed to publish RabbitMQ message: ' . $e->getMessage());
            return false;
        }
    }
    
    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}