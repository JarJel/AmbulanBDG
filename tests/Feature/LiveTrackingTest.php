<?php

use App\Events\AmbulanceLocationUpdated;
use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;

beforeEach(function () {
    // Buat Institusi
    $this->institution = Institution::create([
        'name' => 'Klinik Medika Bandung',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    // Buat Driver & Perawat
    $this->driver = User::create([
        'name' => 'Driver Andi',
        'email' => 'andi@driver.com',
        'password' => bcrypt('password'),
        'phone' => '08123123123',
        'role' => 'driver',
        'institution_id' => $this->institution->id,
    ]);

    $this->nurse = User::create([
        'name' => 'Nurse Susi',
        'email' => 'susi@nurse.com',
        'password' => bcrypt('password'),
        'phone' => '08124124124',
        'role' => 'nurse',
        'institution_id' => $this->institution->id,
    ]);

    // Buat Customer Utama
    $this->customer = User::create([
        'name' => 'Customer Anto',
        'email' => 'anto@customer.com',
        'password' => bcrypt('password'),
        'phone' => '08125125125',
        'role' => 'customer',
    ]);

    // Buat Customer Lain (untuk tes unauthorized)
    $this->otherCustomer = User::create([
        'name' => 'Customer Budi',
        'email' => 'budi@other.com',
        'password' => bcrypt('password'),
        'phone' => '08126126126',
        'role' => 'customer',
    ]);

    // Buat Ambulans
    $this->ambulance = new Ambulance();
    $this->ambulance->institution_id = $this->institution->id;
    $this->ambulance->plate_number = 'D 4444 FF';
    $this->ambulance->category = 'emergency';
    $this->ambulance->status = 'standby';
    $this->ambulance->current_driver_id = $this->driver->id;
    $this->ambulance->current_nurse_id = $this->nurse->id;
    $this->ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $this->ambulance->save();

    // Buat Order Aktif
    $this->order = Order::create([
        'user_id' => $this->customer->id,
        'ambulance_id' => $this->ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Gedung Sate',
    ]);
});

test('it updates tracking location and dispatches broadcast event', function () {
    Event::fake([AmbulanceLocationUpdated::class]);

    $response = $this->postJson("/api/orders/{$this->order->id}/tracking", [
        'latitude' => -6.920000,
        'longitude' => 107.620000,
        'bearing' => 90.5,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');

    // Verifikasi bahwa event AmbulanceLocationUpdated telah dikirim
    Event::assertDispatched(AmbulanceLocationUpdated::class, function ($event) {
        return $event->orderId === $this->order->id &&
               $event->latitude === -6.920000 &&
               $event->longitude === 107.620000 &&
               $event->bearing === 90.5;
    });

    // Verifikasi data koordinat tersimpan di trip_tracking_logs
    $this->assertDatabaseHas('trip_tracking_logs', [
        'order_id' => $this->order->id,
    ]);
});

test('it rejects location updates for completed or inactive orders', function () {
    // Ubah status order menjadi completed
    $this->order->status = 'completed';
    $this->order->save();

    $response = $this->postJson("/api/orders/{$this->order->id}/tracking", [
        'latitude' => -6.920000,
        'longitude' => 107.620000,
    ]);

    $response->assertStatus(400);
    $response->assertJsonPath('status', 'error');
});

test('it authorizes private channel access for authorized users', function () {
    // Pengguna yang memesan (Customer) diizinkan mengakses channel orders.{orderId}
    $responseCustomer = $this->actingAs($this->customer)->postJson('/broadcasting/auth', [
        'channel_name' => "private-orders.{$this->order->id}",
        'socket_id' => '1234.5678',
    ]);
    $responseCustomer->assertStatus(200);

    // Driver yang ditugaskan diizinkan mengakses channel
    $responseDriver = $this->actingAs($this->driver)->postJson('/broadcasting/auth', [
        'channel_name' => "private-orders.{$this->order->id}",
        'socket_id' => '1234.5678',
    ]);
    $responseDriver->assertStatus(200);

    // Nurse yang ditugaskan diizinkan mengakses channel
    $responseNurse = $this->actingAs($this->nurse)->postJson('/broadcasting/auth', [
        'channel_name' => "private-orders.{$this->order->id}",
        'socket_id' => '1234.5678',
    ]);
    $responseNurse->assertStatus(200);
});

test('it denies private channel access for unauthorized users', function () {
    // Pengguna lain yang tidak memiliki kaitan dengan order ditolak mengakses channel
    $responseOther = $this->actingAs($this->otherCustomer)->postJson('/broadcasting/auth', [
        'channel_name' => "private-orders.{$this->order->id}",
        'socket_id' => '1234.5678',
    ]);
    $responseOther->assertStatus(403);
});

test('it flags location update as anomaly if speed exceeds limit', function () {
    Event::fake([AmbulanceLocationUpdated::class]);

    // Set last location update time to 10 seconds ago
    $this->ambulance->last_location_at = now()->subSeconds(10);
    // Set current location to Bandung Center Point(107.619123, -6.917464)
    $this->ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $this->ambulance->save();

    // Send a location update 5 KM away (physically impossible in 10 seconds)
    // 5 KM in 10 seconds = 500 m/s (far exceeds 33.33 m/s limit)
    $response = $this->postJson("/api/orders/{$this->order->id}/tracking", [
        'latitude' => -6.950000,
        'longitude' => 107.619100,
        'bearing' => 180,
    ]);

    $response->assertStatus(202);
    $response->assertJsonPath('status', 'flagged');

    // Make sure it did not broadcast the update event
    Event::assertNotDispatched(AmbulanceLocationUpdated::class);

    // Verify it is recorded in location_anomalies table
    $this->assertDatabaseHas('location_anomalies', [
        'ambulance_id' => $this->ambulance->id,
        'reported_lat' => -6.950000,
        'reported_lng' => 107.619100,
    ]);
});

test('it detects stale tracking signal and broadcasts tracking signal lost event', function () {
    Event::fake([\App\Events\TrackingSignalLost::class]);

    // Set ambulance last_location_at to 3 minutes ago
    $this->ambulance->last_location_at = now()->subMinutes(3);
    $this->ambulance->save();

    // Ensure order is active (on_the_way)
    $this->order->status = 'on_the_way';
    $this->order->save();

    // Run the job
    $job = new \App\Jobs\DetectStaleTracking();
    $job->handle();

    // Verify order is flagged with tracking_lost_notified_at
    $this->order->refresh();
    expect($this->order->tracking_lost_notified_at)->not->toBeNull();

    // Verify event is dispatched
    Event::assertDispatched(\App\Events\TrackingSignalLost::class, function ($event) {
        return $event->order->id === $this->order->id;
    });
});
