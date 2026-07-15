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
        Route::post('sos/guest', [OrderController::class, 'postSosOrderGuest'])->middleware('throttle:guest_sos');
        Route::post('sewa', [OrderController::class, 'postSewaOrder']);
        Route::post('event', [OrderController::class, 'postEventOrder']);
        Route::post('{id}/confirm', [OrderController::class, 'postConfirmOrder']);
        Route::post('{id}/accept', [OrderController::class, 'postAcceptOrder']);
        Route::post('{id}/status', [OrderController::class, 'postUpdateStatus']);
        Route::post('{id}/tracking', [OrderController::class, 'postUpdateLocation']);
        Route::post('{id}/patient-condition', [OrderController::class, 'postUpdatePatientCondition']);
        Route::post('{id}/patient-details', [OrderController::class, 'postSavePatientDetails']);
        Route::post('{id}/payment/guest', [OrderController::class, 'postSavePaymentGuest']);
        Route::post('{id}/guest-register', [OrderController::class, 'postRegisterGuestAccount']);
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
        
        // New Ambulance & Staff Management
        Route::post('ambulances', [AdminController::class, 'postCreateAmbulance']);
        Route::post('ambulances/{id}/clone', [AdminController::class, 'postCloneAmbulance']);
        Route::post('staff', [AdminController::class, 'postCreateStaff']);
        Route::post('staff/{id}/deactivate', [AdminController::class, 'postDeactivateStaff']);
        Route::get('staff/{id}/sessions', [AdminController::class, 'getStaffSessions']);
        Route::post('staff/{id}/logout-device', [AdminController::class, 'postLogoutStaffDevice']);
        
        // Bulk approvals for Super Admin
        Route::post('approvals/bulk', [AdminController::class, 'postBulkApprovals'])->middleware('role:super_admin');
    });

    Route::post('orders/{id}/rating', [RatingController::class, 'postRateOrder']);
    Route::get('orders/{id}/export', [OrderController::class, 'getExportSummary']);

    Route::middleware(['role:driver,nurse'])->prefix('driver')->group(function () {
        Route::post('assign-ambulance', [DriverController::class, 'postAssignAmbulance']);
        Route::post('status', [DriverController::class, 'postToggleStatus']);
        Route::get('active-trips', [DriverController::class, 'getActiveTrips']);
        Route::post('checklist', [DriverController::class, 'postSubmitChecklist']);
    });

    Route::get('radar', [RadarController::class, 'getRadar']);
    Route::get('geocode', [RadarController::class, 'getGeocode']);
    Route::get('reverse-geocode', [RadarController::class, 'getReverseGeocode']);
});
