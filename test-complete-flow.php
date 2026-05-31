<?php

/**
 * SCRIPT DE TEST COMPLET - SYSTÈME DE LIVRAISON
 * 
 * Ce script teste le flux complet :
 * 1. Register client
 * 2. Login
 * 3. Créer livraison
 * 4. Assigner livreur
 * 5. Changer statut
 * 6. Vérifier notifications
 */

class DeliverySystemTester
{
    private $authBaseUrl = 'http://localhost:8000/api';
    private $deliveryBaseUrl = 'http://localhost:8001/api';
    private $notificationBaseUrl = 'http://localhost:8002/api';
    
    private $clientToken = null;
    private $livreurToken = null;
    private $deliveryId = null;
    
    public function runCompleteTest()
    {
        echo "🚀 DÉBUT DES TESTS COMPLETS DU SYSTÈME DE LIVRAISON\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        try {
            // Étape 1: Register Client
            $this->testRegisterClient();
            
            // Étape 2: Register Livreur
            $this->testRegisterLivreur();
            
            // Étape 3: Login Client
            $this->testLoginClient();
            
            // Étape 4: Login Livreur
            $this->testLoginLivreur();
            
            // Étape 5: Créer Livraison
            $this->testCreateDelivery();
            
            // Étape 6: Assigner Livreur
            $this->testAssignDelivery();
            
            // Étape 7: Changer Statut
            $this->testChangeStatus();
            
            // Étape 8: Vérifier Historique
            $this->testGetHistory();
            
            echo "\n🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS !\n";
            
        } catch (Exception $e) {
            echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function testRegisterClient()
    {
        echo "📝 Test 1: Register Client\n";
        
        $data = [
            'name' => 'Client Test',
            'email' => 'client@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'client'
        ];
        
        $response = $this->makeRequest('POST', $this->authBaseUrl . '/register', $data);
        
        if ($response['status'] !== true) {
            throw new Exception('Échec du register client: ' . json_encode($response));
        }
        
        echo "✅ Client enregistré avec succès\n\n";
    }
    
    private function testRegisterLivreur()
    {
        echo "📝 Test 2: Register Livreur\n";
        
        $data = [
            'name' => 'Livreur Test',
            'email' => 'livreur@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'livreur'
        ];
        
        $response = $this->makeRequest('POST', $this->authBaseUrl . '/register', $data);
        
        if ($response['status'] !== true) {
            throw new Exception('Échec du register livreur: ' . json_encode($response));
        }
        
        echo "✅ Livreur enregistré avec succès\n\n";
    }
    
    private function testLoginClient()
    {
        echo "🔐 Test 3: Login Client\n";
        
        $data = [
            'email' => 'client@test.com',
            'password' => 'password123'
        ];
        
        $response = $this->makeRequest('POST', $this->authBaseUrl . '/login', $data);
        
        if ($response['status'] !== true || !isset($response['token'])) {
            throw new Exception('Échec du login client: ' . json_encode($response));
        }
        
        $this->clientToken = $response['token'];
        echo "✅ Client connecté avec succès\n";
        echo "   Token: " . substr($this->clientToken, 0, 20) . "...\n\n";
    }
    
    private function testLoginLivreur()
    {
        echo "🔐 Test 4: Login Livreur\n";
        
        $data = [
            'email' => 'livreur@test.com',
            'password' => 'password123'
        ];
        
        $response = $this->makeRequest('POST', $this->authBaseUrl . '/login', $data);
        
        if ($response['status'] !== true || !isset($response['token'])) {
            throw new Exception('Échec du login livreur: ' . json_encode($response));
        }
        
        $this->livreurToken = $response['token'];
        echo "✅ Livreur connecté avec succès\n";
        echo "   Token: " . substr($this->livreurToken, 0, 20) . "...\n\n";
    }
    
    private function testCreateDelivery()
    {
        echo "📦 Test 5: Créer Livraison\n";
        
        $data = [
            'client_id' => 1,
            'adresse' => '123 Rue de la Paix, 75001 Paris'
        ];
        
        $response = $this->makeRequest('POST', $this->deliveryBaseUrl . '/deliveries', $data, $this->clientToken);
        
        if ($response['status'] !== true || !isset($response['delivery']['id'])) {
            throw new Exception('Échec de création de livraison: ' . json_encode($response));
        }
        
        $this->deliveryId = $response['delivery']['id'];
        echo "✅ Livraison créée avec succès\n";
        echo "   ID: {$this->deliveryId}\n";
        echo "   Statut: {$response['delivery']['status']}\n\n";
    }
    
    private function testAssignDelivery()
    {
        echo "👤 Test 6: Assigner Livreur\n";
        
        $data = [
            'livreur_id' => 2
        ];
        
        $response = $this->makeRequest('PUT', $this->deliveryBaseUrl . "/deliveries/{$this->deliveryId}/assign", $data, $this->livreurToken);
        
        if ($response['status'] !== true) {
            throw new Exception('Échec d\'assignation du livreur: ' . json_encode($response));
        }
        
        echo "✅ Livreur assigné avec succès\n";
        echo "   Statut: {$response['delivery']['status']}\n\n";
        
        // Attendre un peu pour que RabbitMQ traite le message
        sleep(2);
    }
    
    private function testChangeStatus()
    {
        echo "🔄 Test 7: Changer Statut\n";
        
        $statuses = ['en_cours', 'livrée'];
        
        foreach ($statuses as $status) {
            $data = [
                'status' => $status,
                'comment' => "Test automatique - changement vers {$status}"
            ];
            
            $response = $this->makeRequest('PUT', $this->deliveryBaseUrl . "/deliveries/{$this->deliveryId}/status", $data, $this->livreurToken);
            
            if ($response['status'] !== true) {
                throw new Exception("Échec du changement de statut vers {$status}: " . json_encode($response));
            }
            
            echo "✅ Statut changé vers: {$status}\n";
            
            // Attendre un peu pour que RabbitMQ traite le message
            sleep(2);
        }
        
        echo "\n";
    }
    
    private function testGetHistory()
    {
        echo "📋 Test 8: Vérifier Historique\n";
        
        $response = $this->makeRequest('GET', $this->deliveryBaseUrl . "/deliveries/{$this->deliveryId}/history", [], $this->clientToken);
        
        if ($response['status'] !== true || !isset($response['history'])) {
            throw new Exception('Échec de récupération de l\'historique: ' . json_encode($response));
        }
        
        $historyCount = count($response['history']);
        echo "✅ Historique récupéré avec succès\n";
        echo "   Nombre d'entrées: {$historyCount}\n";
        
        foreach ($response['history'] as $entry) {
            echo "   - {$entry['old_status']} → {$entry['new_status']} ({$entry['created_at']})\n";
        }
        
        echo "\n";
    }
    
    private function makeRequest($method, $url, $data = [], $token = null)
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('Erreur cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception("Erreur HTTP {$httpCode}: " . $response);
        }
        
        return $decodedResponse;
    }
}

// Exécuter les tests
if (php_sapi_name() === 'cli') {
    $tester = new DeliverySystemTester();
    $tester->runCompleteTest();
} else {
    echo "Ce script doit être exécuté en ligne de commande.\n";
}