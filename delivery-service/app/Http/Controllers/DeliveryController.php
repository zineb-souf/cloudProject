<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
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

        $delivery->update([
            'livreur_id' => $request->livreur_id,
            'status'     => 'assignée',
        ]);

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

        $delivery->update([
            'status' => $request->status,
        ]);

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
}
