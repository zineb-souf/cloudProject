<?php

use App\Http\Controllers\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::post('/deliveries', [DeliveryController::class, 'createDelivery']);
Route::put('/deliveries/{id}/assign', [DeliveryController::class, 'assignDelivery']);
Route::put('/deliveries/{id}/status', [DeliveryController::class, 'changeStatus']);
Route::get('/deliveries', [DeliveryController::class, 'getMyDeliveries']);
Route::get('/deliveries/{id}/history', [DeliveryController::class, 'getDeliveryHistory']);
