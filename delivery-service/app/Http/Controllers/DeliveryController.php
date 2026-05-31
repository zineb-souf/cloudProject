<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\DeliveryHistory;
use App\Services\RabbitMQService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    protected $rabbitMQService;
    
    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

    /**
     * Créer une nouvelle livraison (par un client)
     */
    public function createDelivery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|integer',
            'adresse'   => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = Delivery::create([
            'client_id'  => $request->client_id,
            'livreur_id' => null,
            'adresse'    => $request->adresse,
            'status'     => 'en_attente',
        ]);

        return response()->json([
            'status'   => true,
            'message'  => 'Livraison créée avec succès',
            'delivery' => $delivery
        ], 201);
    }

    /**
     * Assigner un livreur à une livraison
     */
    public function assignDelivery(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'livreur_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status'  => false,
                'message' => 'Livraison introuvable'
            ], 404);
        }

        $oldStatus = $delivery->status;
        $newStatus = 'assignée';

        // Enregistrer l'historique
        DeliveryHistory::create([
            'delivery_id' => $delivery->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'comment' => "Livreur #{$request->livreur_id} assigné"
        ]);

        $delivery->update([
            'livreur_id' => $request->livreur_id,
            'status'     => $newStatus,
        ]);

        // Envoyer message RabbitMQ pour changement de statut
        $this->rabbitMQService->publishDeliveryStatusChange($delivery->id, $newStatus);

        return response()->json([
            'status'   => true,
            'message'  => 'Livreur assigné avec succès',
            'delivery' => $delivery
        ]);
    }

    /**
     * Changer le statut d'une livraison
     * Statuts possibles : en_attente, assignée, en_cours, livrée, annulée
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:en_attente,assignée,en_cours,livrée,annulée',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status'  => false,
                'message' => 'Livraison introuvable'
            ], 404);
        }

        $oldStatus = $delivery->status;
        $newStatus = $request->status;

        // Ne pas enregistrer si le statut n'a pas changé
        if ($oldStatus === $newStatus) {
            return response()->json([
                'status'   => true,
                'message'  => 'Statut inchangé',
                'delivery' => $delivery
            ]);
        }

        // Enregistrer l'historique
        DeliveryHistory::create([
            'delivery_id' => $delivery->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
            'comment' => $request->comment ?? "Changement de statut: {$oldStatus} → {$newStatus}"
        ]);

        $delivery->update([
            'status' => $newStatus,
        ]);

        // Envoyer message RabbitMQ pour changement de statut
        $this->rabbitMQService->publishDeliveryStatusChange($delivery->id, $newStatus);

        return response()->json([
            'status'   => true,
            'message'  => 'Statut mis à jour avec succès',
            'delivery' => $delivery
        ]);
    }

    /**
     * Récupérer les livraisons d'un client ou d'un livreur
     * Query params : ?client_id=1  ou  ?livreur_id=2
     */
    public function getMyDeliveries(Request $request)
    {
        $query = Delivery::query();

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        } elseif ($request->has('livreur_id')) {
            $query->where('livreur_id', $request->livreur_id);
        } else {
            return response()->json([
                'status'  => false,
                'message' => 'Paramètre client_id ou livreur_id requis'
            ], 400);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status'     => true,
            'deliveries' => $deliveries
        ]);
    }

    /**
     * Récupérer l'historique d'une livraison
     */
    public function getDeliveryHistory($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json([
                'status'  => false,
                'message' => 'Livraison introuvable'
            ], 404);
        }

        $history = DeliveryHistory::where('delivery_id', $id)
            ->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'delivery' => $delivery,
            'history' => $history
        ]);
    }
}
