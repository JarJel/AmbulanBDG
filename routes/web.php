<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    $activeSosOrder = null;
    if (auth()->check()) {
        $activeSosOrder = \App\Models\Order::with([
            'ambulance.institution',
            'ambulance.currentDriver',
            'ambulance.currentNurse'
        ])
        ->where('user_id', auth()->id())
        ->whereNotIn('status', ['completed', 'cancelled', 'no_ambulance_available'])
        ->latest()
        ->first();
        
        if ($activeSosOrder && $activeSosOrder->ambulance) {
            $coords = DB::selectOne("SELECT ST_X(current_location) as lng, ST_Y(current_location) as lat FROM ambulances WHERE id = ?", [$activeSosOrder->ambulance_id]);
            if ($coords) {
                $activeSosOrder->ambulance->latitude = $coords->lat;
                $activeSosOrder->ambulance->longitude = $coords->lng;
            }
        }
    }

    return inertia('welcome', [
        'initialActiveSosOrder' => $activeSosOrder
    ]);
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/orders/{id}/tracking', function ($id) {
    $order = \App\Models\Order::with(['ambulance.institution', 'ambulance.currentDriver', 'ambulance.currentNurse'])->findOrFail($id);
    if ($order->ambulance) {
        $coords = DB::selectOne("SELECT ST_X(current_location) as lng, ST_Y(current_location) as lat FROM ambulances WHERE id = ?", [$order->ambulance_id]);
        if ($coords) {
            $order->ambulance->latitude = $coords->lat;
            $order->ambulance->longitude = $coords->lng;
        }
    }
    return inertia('orders/tracking', [
        'order' => $order
    ]);
})->name('orders.tracking');

Route::get('/driver/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    $ambulances = \App\Models\Ambulance::all();
    return inertia('driver/dashboard', [
        'ambulances' => $ambulances,
        'currentUser' => auth()->user()
    ]);
})->name('driver.dashboard');
