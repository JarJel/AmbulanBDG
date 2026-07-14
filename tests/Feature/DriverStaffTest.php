<?php

use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('it allows driver and nurse to assign themselves to an ambulance', function () {
    // 1. Setup
    $driver = User::create([
        'name' => 'Joko Driver',
        'email' => 'jokodriver@ambulanbdg.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
    ]);

    $nurse = User::create([
        'name' => 'Siti Nurse',
        'email' => 'sitinurse@ambulanbdg.com',
        'password' => bcrypt('password'),
        'role' => 'nurse',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Pajajaran',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1234 DP';
    $ambulance->category = 'emergency';
    $ambulance->status = 'offline';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // 2. Assign Driver
    $responseDriver = $this->actingAs($driver)->postJson('/api/driver/assign-ambulance', [
        'ambulance_id' => $ambulance->id,
    ]);

    $responseDriver->assertStatus(200);
    $responseDriver->assertJsonPath('ambulance.current_driver_id', $driver->id);
    $responseDriver->assertJsonPath('ambulance.status', 'standby');

    // 3. Assign Nurse
    $responseNurse = $this->actingAs($nurse)->postJson('/api/driver/assign-ambulance', [
        'ambulance_id' => $ambulance->id,
    ]);

    $responseNurse->assertStatus(200);
    $responseNurse->assertJsonPath('ambulance.current_nurse_id', $nurse->id);

    // 4. Verify Audit Log
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $driver->id,
        'action' => 'assign_ambulance_staff',
    ]);
});

test('it restricts toggle status unless driver is assigned to an ambulance', function () {
    // 1. Setup Driver
    $driver = User::create([
        'name' => 'Joko Driver 2',
        'email' => 'jokodriver2@ambulanbdg.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Pajajaran',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 5678 DP';
    $ambulance->category = 'emergency';
    $ambulance->status = 'offline';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // A. Try to toggle status when not assigned yet -> 400
    $responseFailed = $this->actingAs($driver)->postJson('/api/driver/status', [
        'status' => 'standby',
    ]);
    $responseFailed->assertStatus(400);
    $responseFailed->assertJsonPath('message', 'Anda harus terikat ke armada ambulans terlebih dahulu.');

    // B. Assign driver first
    $this->actingAs($driver)->postJson('/api/driver/assign-ambulance', [
        'ambulance_id' => $ambulance->id,
    ]);

    // C. Try to toggle status to offline -> 200
    $responseSuccess = $this->actingAs($driver)->postJson('/api/driver/status', [
        'status' => 'offline',
    ]);
    $responseSuccess->assertStatus(200);
    $responseSuccess->assertJsonPath('ambulance.status', 'offline');

    // D. Verify Audit Log
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $driver->id,
        'action' => 'toggle_ambulance_status',
    ]);
});

test('it returns active trip details for assigned driver', function () {
    // 1. Setup
    $driver = User::create([
        'name' => 'Joko Driver 3',
        'email' => 'jokodriver3@ambulanbdg.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
    ]);

    $customer = User::create([
        'name' => 'Customer Adit',
        'email' => 'adit_driver_test@cust.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Pajajaran',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 9012 DP';
    $ambulance->category = 'emergency';
    $ambulance->status = 'offline';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // 2. Assign Driver
    $this->actingAs($driver)->postJson('/api/driver/assign-ambulance', [
        'ambulance_id' => $ambulance->id,
    ]);

    // 3. Create active order for this ambulance
    $order = Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Gedung Sate',
        'fare_amount' => 0.00,
    ]);

    // 4. Get active trip
    $response = $this->actingAs($driver)->getJson('/api/driver/active-trips');
    $response->assertStatus(200);
    $response->assertJsonPath('order.id', $order->id);
    $response->assertJsonPath('order.status', 'confirmed');
});
