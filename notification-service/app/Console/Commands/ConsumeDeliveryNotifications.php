<?php

namespace App\Console\Commands;

use App\Services\RabbitMQConsumer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('rabbitmq:consume-delivery-notifications')]
#[Description('Consommer les messages RabbitMQ pour les changements de statut de livraison')]
class ConsumeDeliveryNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Démarrage du consumer RabbitMQ pour les notifications de livraison...');
        
        try {
            $consumer = new RabbitMQConsumer();
            $consumer->consumeDeliveryStatusChanges();
        } catch (\Exception $e) {
            $this->error('Erreur du consumer RabbitMQ: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
