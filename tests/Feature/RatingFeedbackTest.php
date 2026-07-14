<?php

use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('it validates rating requirements and ownership', function () {
    // 1. Setup users
    $customerA = User::create([
        'name' => 'Adit Cust A',
        'email' => 'adita@ratings.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $customerB = User::create([
        'name' => 'Budi Cust B',
        'email' => 'budib@ratings.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Sukajadi',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1111 ZZ';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Completed Order
    $orderCompleted = Order::create([
        'user_id' => $customerA->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'completed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan Merdeka No. 1',
        'fare_amount' => 0.00,
    ]);

    // Active/Confirmed Order (not completed)
    $orderActive = Order::create([
        'user_id' => $customerA->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan Merdeka No. 1',
        'fare_amount' => 0.00,
    ]);

    // A. Customer B tries to rate Customer A's completed order -> 403
    $responseOther = $this->actingAs($customerB)->postJson("/api/orders/{$orderCompleted->id}/rating", [
        'rating_value' => 5,
        'review_text' => 'Bagus sekali!',
    ]);
    $responseOther->assertStatus(403);
    $responseOther->assertJsonPath('message', 'Anda tidak memiliki hak akses untuk memberikan ulasan pada pesanan ini.');

    // B. Customer A tries to rate an active/confirmed order -> 400
    $responseActive = $this->actingAs($customerA)->postJson("/api/orders/{$orderActive->id}/rating", [
        'rating_value' => 5,
        'review_text' => 'Bagus sekali!',
    ]);
    $responseActive->assertStatus(400);
    $responseActive->assertJsonPath('message', 'Hanya pesanan yang telah selesai yang dapat diberikan ulasan.');

    // C. Customer A rates completed order successfully -> 201
    $responseSuccess = $this->actingAs($customerA)->postJson("/api/orders/{$orderCompleted->id}/rating", [
        'rating_value' => 5,
        'review_text' => 'Sangat cepat dan ramah.',
    ]);
    $responseSuccess->assertStatus(201);
    $responseSuccess->assertJsonPath('rating.rating_value', 5);

    // D. Customer A tries to rate completed order again -> 400
    $responseDuplicate = $this->actingAs($customerA)->postJson("/api/orders/{$orderCompleted->id}/rating", [
        'rating_value' => 4,
        'review_text' => 'Mengubah pikiran.',
    ]);
    $responseDuplicate->assertStatus(400);
    $responseDuplicate->assertJsonPath('message', 'Pesanan ini sudah pernah diberikan ulasan.');
});

test('it calculates dynamic average rating for ambulance correctly', function () {
    $customer = User::create([
        'name' => 'Adit Cust',
        'email' => 'adit@ratings_avg.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Sukajadi',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 2222 YY';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Order 1
    $order1 = Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'completed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan A',
        'fare_amount' => 0.00,
    ]);

    // Order 2
    $order2 = Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'completed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan B',
        'fare_amount' => 150000.00,
    ]);

    // Rate Order 1: 5 stars
    Rating::create([
        'order_id' => $order1->id,
        'rating_value' => 5,
    ]);

    // Rate Order 2: 3 stars
    Rating::create([
        'order_id' => $order2->id,
        'rating_value' => 3,
    ]);

    // Average should be (5 + 3) / 2 = 4.0
    $ambulanceFresh = Ambulance::find($ambulance->id);
    expect($ambulanceFresh->average_rating)->toBe(4.0);
});
