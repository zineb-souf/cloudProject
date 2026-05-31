<?php

namespace App\Services;

use App\Models\Notification;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class RabbitMQConsumer
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
            \Log::error('RabbitMQ connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function consumeDeliveryStatusChanges()
    {
        // Déclarer la queue
        $this->channel->queue_declare('delivery_status_changes', false, true, false, false);
        
        echo " [*] En attente de messages. Pour quitter, appuyez sur CTRL+C\n";
        
        $callback = function (AMQPMessage $msg) {
            try {
                $data = json_decode($msg->body, true);
                
                if (!$data || !isset($data['delivery_id']) || !isset($data['status'])) {
                    echo " [x] Message invalide reçu: " . $msg->body . "\n";
                    $msg->ack();
                    return;
                }
                
                // Créer la notification
                $notification = Notification::create([
                    'delivery_id' => $data['delivery_id'],
                    'status' => $data['status'],
                    'message' => $this->generateMessage($data['status'], $data['delivery_id']),
                    'data' => $data,
                    'read' => false,
                ]);
                
                echo " [x] Notification créée: ID {$notification->id} pour livraison {$data['delivery_id']} - Statut: {$data['status']}\n";
                
                // Acquitter le message
                $msg->ack();
                
            } catch (Exception $e) {
                echo " [x] Erreur lors du traitement du message: " . $e->getMessage() . "\n";
                // Rejeter le message sans le remettre en queue
                $msg->nack(false, false);
            }
        };
        
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume('delivery_status_changes', '', false, false, false, false, $callback);
        
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
    
    private function generateMessage($status, $deliveryId)
    {
        $messages = [
            'en_attente' => "La livraison #{$deliveryId} est en attente d'assignation",
            'assignée' => "Un livreur a été assigné à la livraison #{$deliveryId}",
            'en_cours' => "La livraison #{$deliveryId} est en cours de livraison",
            'livrée' => "La livraison #{$deliveryId} a été livrée avec succès",
            'annulée' => "La livraison #{$deliveryId} a été annulée",
        ];
        
        return $messages[$status] ?? "Statut de la livraison #{$deliveryId} mis à jour: {$status}";
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