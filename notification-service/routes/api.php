<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/notifications', [NotificationController::class, 'getAllNotifications']);
Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
Route::get('/notifications/delivery/{deliveryId}', [NotificationController::class, 'getDeliveryNotifications']);
Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'deleteNotification']);
