<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Récupérer les notifications d'une livraison
     */
    public function getDeliveryNotifications($deliveryId)
    {
        $notifications = Notification::where('delivery_id', $deliveryId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'delivery_id' => $deliveryId,
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read', false)->count()
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification introuvable'
            ], 404);
        }

        $notification->update(['read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marquée comme lue',
            'notification' => $notification
        ]);
    }

    /**
     * Récupérer toutes les notifications non lues
     */
    public function getUnreadNotifications()
    {
        $notifications = Notification::where('read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'unread_count' => $notifications->count(),
            'notifications' => $notifications
        ]);
    }

    /**
     * Récupérer toutes les notifications
     */
    public function getAllNotifications(Request $request)
    {
        $query = Notification::query();

        if ($request->has('delivery_id')) {
            $query->where('delivery_id', $request->delivery_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('read')) {
            $query->where('read', $request->read === 'true');
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function deleteNotification($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification introuvable'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notification supprimée avec succès'
        ]);
    }
}
