<?php

use App\Models\Institution;
use App\Models\User;
use App\Models\Ambulance;
use App\Models\VehicleDocument;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\SosLog;
use App\Models\TripStatusHistory;
use App\Models\TripTrackingLog;
use Illuminate\Support\Facades\DB;

test('it can create database records and verify all relationships including spatial point', function () {
    // 1. Create Institution
    $institution = Institution::create([
        'name' => 'RS Hasan Sadikin Bandung',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    expect($institution->name)->toBe('RS Hasan Sadikin Bandung');

    // 2. Create User (Driver) linked to Institution
    $driverUser = User::create([
        'name' => 'Ahmad Driver',
        'email' => 'ahmad@driver.com',
        'password' => bcrypt('password123'),
        'phone' => '081234567890',
        'role' => 'driver',
        'institution_id' => $institution->id,
    ]);

    expect($driverUser->institution->id)->toBe($institution->id);

    // 3. Create User (Customer)
    $customerUser = User::create([
        'name' => 'Budi Customer',
        'email' => 'budi@customer.com',
        'password' => bcrypt('password123'),
        'phone' => '081298765432',
        'role' => 'customer',
    ]);

    expect($customerUser->institution)->toBeNull();

    // 4. Create Ambulance
    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1111 AB';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_driver_id = $driverUser->id;
    // Set location using spatial POINT
    $ambulance->current_location = DB::raw("Point(-6.917464, 107.619123)");
    $ambulance->save();

    // Refresh model to load
    $ambulance = Ambulance::find($ambulance->id);

    expect($ambulance->plate_number)->toBe('D 1111 AB');
    expect($ambulance->institution->id)->toBe($institution->id);
    expect($ambulance->currentDriver->id)->toBe($driverUser->id);

    // 5. Create Vehicle Document
    $document = VehicleDocument::create([
        'ambulance_id' => $ambulance->id,
        'doc_type' => 'STNK',
        'document_path' => '/uploads/stnk/d_1111_ab.pdf',
        'verification_status' => 'approved',
        'expiry_date' => now()->addYear(),
    ]);

    expect($ambulance->vehicleDocuments->first()->id)->toBe($document->id);

    // 6. Create Order
    $order = new Order();
    $order->user_id = $customerUser->id;
    $order->ambulance_id = $ambulance->id;
    $order->order_type = 'SOS';
    $order->status = 'confirmed';
    $order->pickup_location = DB::raw("Point(-6.917464, 107.619123)");
    $order->pickup_address = 'Jalan Diponegoro No. 22, Bandung';
    $order->destination_location = DB::raw("Point(-6.90389, 107.61861)");
    $order->destination_address = 'RS Hasan Sadikin, Bandung';
    $order->fare_amount = 0.00;
    $order->bpjs_number = '0001234567890';
    $order->bpjs_status = 'verified';
    $order->save();

    // Refresh and check relation
    $order = Order::find($order->id);
    expect($order->user->id)->toBe($customerUser->id);
    expect($order->ambulance->id)->toBe($ambulance->id);
    expect($order->pickup_address)->toBe('Jalan Diponegoro No. 22, Bandung');

    // 7. Create Payment
    $payment = Payment::create([
        'order_id' => $order->id,
        'method' => 'cash',
        'amount' => 0.00,
        'status' => 'paid',
    ]);

    expect($order->payment->id)->toBe($payment->id);

    // 8. Create Rating
    $rating = Rating::create([
        'order_id' => $order->id,
        'rating_value' => 5,
        'review_text' => 'Pelayanan cepat dan ramah.',
    ]);

    expect($order->rating->id)->toBe($rating->id);

    // 9. Create SOS Log
    $sosLog = SosLog::create([
        'order_id' => $order->id,
        'response_time_seconds' => 45,
        'escalated' => false,
    ]);

    expect($order->sosLog->id)->toBe($sosLog->id);

    // 10. Create Trip Status History
    $history = TripStatusHistory::create([
        'order_id' => $order->id,
        'status' => 'confirmed',
        'changed_at' => now(),
    ]);

    expect($order->statusHistory->first()->status)->toBe('confirmed');

    // 11. Create Trip Tracking Log
    $trackingLog = new TripTrackingLog();
    $trackingLog->order_id = $order->id;
    $trackingLog->coordinates = DB::raw("Point(-6.917464, 107.619123)");
    $trackingLog->recorded_at = now();
    $trackingLog->save();

    expect($order->trackingLogs->first()->order_id)->toBe($order->id);
});
