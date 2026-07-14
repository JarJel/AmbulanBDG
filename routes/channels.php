<?php

use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::with('ambulance')->find($orderId);
    
    if (!$order) {
        return false;
    }
    
    // Otorisasi:
    // 1. User adalah pelanggan yang memesan order tersebut
    if ((int) $order->user_id === (int) $user->id) {
        return true;
    }
    
    // 2. User adalah driver yang ditugaskan pada ambulans tersebut
    if ($order->ambulance && (int) $order->ambulance->current_driver_id === (int) $user->id) {
        return true;
    }
    
    // 3. User adalah perawat yang ditugaskan pada ambulans tersebut
    if ($order->ambulance && (int) $order->ambulance->current_nurse_id === (int) $user->id) {
        return true;
    }
    
    // 4. User adalah staf institusi/fasyankes pemilik ambulans tersebut
    if ($order->ambulance && (int) $order->ambulance->institution_id === (int) $user->institution_id) {
        return true;
    }
    
    // 5. Super Admin
    if ($user->role === 'super_admin') {
        return true;
    }
    
    return false;
});
