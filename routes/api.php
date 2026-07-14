<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RadarController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::prefix('orders')->group(function () {
        Route::post('sos', [OrderController::class, 'postSosOrder']);
        Route::post('sewa', [OrderController::class, 'postSewaOrder']);
        Route::post('event', [OrderController::class, 'postEventOrder']);
        Route::post('{id}/confirm', [OrderController::class, 'postConfirmOrder']);
        Route::post('{id}/accept', [OrderController::class, 'postAcceptOrder']);
        Route::post('{id}/status', [OrderController::class, 'postUpdateStatus']);
        Route::post('{id}/tracking', [OrderController::class, 'postUpdateLocation']);
        Route::get('{id}', [OrderController::class, 'getOrder']);
    });

    Route::get('test-rbac-admin', function () {
        return response()->json(['message' => 'welcome admin']);
    })->middleware(['role:super_admin']);

    Route::middleware(['role:super_admin,institution_admin'])->prefix('admin')->group(function () {
        Route::post('documents/{id}/verify', [AdminController::class, 'postVerifyDocument']);
        Route::post('staff/{id}/verify', [AdminController::class, 'postVerifyStaff']);
        Route::post('ambulances/{id}/manage', [AdminController::class, 'postManageAmbulance']);
        Route::post('event-proposals/{id}/verify', [AdminController::class, 'postVerifyEventProposal']);
    });

    Route::post('orders/{id}/rating', [RatingController::class, 'postRateOrder'])->middleware(['role:customer']);

    Route::middleware(['role:driver,nurse'])->prefix('driver')->group(function () {
        Route::post('assign-ambulance', [DriverController::class, 'postAssignAmbulance']);
        Route::post('status', [DriverController::class, 'postToggleStatus']);
        Route::get('active-trips', [DriverController::class, 'getActiveTrips']);
    });

    Route::get('radar', [RadarController::class, 'getRadar']);
    Route::get('geocode', [RadarController::class, 'getGeocode']);
    Route::get('reverse-geocode', [RadarController::class, 'getReverseGeocode']);
});
