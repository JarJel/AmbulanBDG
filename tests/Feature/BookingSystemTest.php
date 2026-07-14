<?php

use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\User;
use App\Models\Order;
use App\Models\EventProposal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Buat Institusi
    $this->institution = Institution::create([
        'name' => 'RS Bandung Raya',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    // Buat Driver
    $this->driver = User::create([
        'name' => 'Driver Jono',
        'email' => 'jono@driver.com',
        'password' => bcrypt('password'),
        'phone' => '08122334455',
        'role' => 'driver',
        'institution_id' => $this->institution->id,
    ]);

    // Buat Customer
    $this->customer = User::create([
        'name' => 'Customer Rian',
        'email' => 'rian@customer.com',
        'password' => bcrypt('password'),
        'phone' => '081299887766',
        'role' => 'customer',
    ]);
});

test('it assigns nearest standby ambulance for SOS orders and moves to awaiting_driver_response', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Event::fake([\App\Events\TripStatusUpdated::class]);
    // Buat 2 Ambulans Standby dengan koordinat lokasi berbeda
    // Ambulans A (Lebih dekat ke titik user)
    $ambulanceA = new Ambulance();
    $ambulanceA->institution_id = $this->institution->id;
    $ambulanceA->plate_number = 'D 9999 AA';
    $ambulanceA->category = 'emergency';
    $ambulanceA->status = 'standby';
    $ambulanceA->current_driver_id = $this->driver->id;
    $ambulanceA->current_location = DB::raw("Point(107.619123, -6.917464)"); // Bandung Center
    $ambulanceA->save();

    // Ambulans B (Lebih jauh)
    $ambulanceB = new Ambulance();
    $ambulanceB->institution_id = $this->institution->id;
    $ambulanceB->plate_number = 'D 8888 BB';
    $ambulanceB->category = 'emergency';
    $ambulanceB->status = 'standby';
    $ambulanceB->current_location = DB::raw("Point(107.677567, -6.924901)"); // Antapani
    $ambulanceB->save();

    // Request SOS
    $response = $this->postJson('/api/orders/sos', [
        'user_id' => $this->customer->id,
        'pickup_latitude' => -6.917500, // Dekat dengan Ambulans A
        'pickup_longitude' => 107.619100,
        'pickup_address' => 'Gedung Sate Bandung',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('status', 'success');

    $orderId = $response->json('order.id');

    // Pastikan Ambulans A yang ditugaskan (karena lebih dekat) berstatus awaiting_driver_response
    $this->assertDatabaseHas('orders', [
        'id' => $orderId,
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulanceA->id,
        'order_type' => 'SOS',
        'status' => 'awaiting_driver_response',
    ]);

    // Pastikan status Ambulans A berubah menjadi 'dispatching'
    expect(Ambulance::find($ambulanceA->id)->status)->toBe('dispatching');

    // Driver accepts the order
    $responseAccept = $this->actingAs($this->driver)->postJson("/api/orders/{$orderId}/accept");
    $responseAccept->assertStatus(200);

    // Pastikan status order menjadi confirmed dan status Ambulans A menjadi on_duty
    $this->assertDatabaseHas('orders', [
        'id' => $orderId,
        'status' => 'confirmed',
    ]);
    expect(Ambulance::find($ambulanceA->id)->status)->toBe('on_duty');
});

test('it handles SLA reassign when driver does not accept SOS order within SLA time', function () {
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Event::fake([\App\Events\TripStatusUpdated::class]);
    // Setup 2 standby ambulances
    $ambulanceA = new Ambulance();
    $ambulanceA->institution_id = $this->institution->id;
    $ambulanceA->plate_number = 'D 9999 AA';
    $ambulanceA->category = 'emergency';
    $ambulanceA->status = 'standby';
    $ambulanceA->current_driver_id = $this->driver->id;
    $ambulanceA->current_location = DB::raw("Point(107.619123, -6.917464)"); // Bandung Center
    $ambulanceA->save();

    // Secondary driver for ambulance B
    $driverB = User::create([
        'name' => 'Driver Joko',
        'email' => 'joko@driver.com',
        'password' => bcrypt('password'),
        'phone' => '08122334466',
        'role' => 'driver',
        'institution_id' => $this->institution->id,
    ]);

    $ambulanceB = new Ambulance();
    $ambulanceB->institution_id = $this->institution->id;
    $ambulanceB->plate_number = 'D 8888 BB';
    $ambulanceB->category = 'emergency';
    $ambulanceB->status = 'standby';
    $ambulanceB->current_driver_id = $driverB->id;
    $ambulanceB->current_location = DB::raw("Point(107.620000, -6.917000)"); // Dekat juga
    $ambulanceB->save();

    // Dispatch SOS (should assign to A first)
    $response = $this->postJson('/api/orders/sos', [
        'user_id' => $this->customer->id,
        'pickup_latitude' => -6.917500,
        'pickup_longitude' => 107.619100,
        'pickup_address' => 'Gedung Sate Bandung',
    ]);
    $response->assertStatus(201);
    $orderId = $response->json('order.id');

    expect(Ambulance::find($ambulanceA->id)->status)->toBe('dispatching');
    expect(Order::find($orderId)->ambulance_id)->toBe($ambulanceA->id);

    // Simulate SLA Timeout by running the job manually
    $job = new \App\Jobs\AutoReassignSosOrder($orderId, $ambulanceA->id);
    $job->handle(app(\App\Services\SosDispatchService::class));

    // Ambulance A must be returned to standby
    expect(Ambulance::find($ambulanceA->id)->status)->toBe('standby');

    // Order must be reassigned to Ambulance B
    $order = Order::find($orderId);
    expect($order->ambulance_id)->toBe($ambulanceB->id);
    expect($order->status)->toBe('awaiting_driver_response');
    expect(Ambulance::find($ambulanceB->id)->status)->toBe('dispatching');
});

test('it returns fallback error if no ambulance is standby for SOS', function () {
    // Request SOS ketika tidak ada ambulans standby
    $response = $this->postJson('/api/orders/sos', [
        'user_id' => $this->customer->id,
        'pickup_latitude' => -6.917500,
        'pickup_longitude' => 107.619100,
        'pickup_address' => 'Gedung Sate Bandung',
    ]);

    $response->assertStatus(404);
    $response->assertJsonPath('status', 'no_ambulance_available');
    $response->assertJsonPath('fallback_number', '112');
});

test('it creates sewa order and calculates fare and BPJS coverage', function () {
    $ambulance = new Ambulance();
    $ambulance->institution_id = $this->institution->id;
    $ambulance->plate_number = 'D 7777 CC';
    $ambulance->category = 'non-emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Pesan Sewa dengan BPJS valid (dimulai dengan 000)
    $response = $this->postJson('/api/orders/sewa', [
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulance->id,
        'pickup_latitude' => -6.917464,
        'pickup_longitude' => 107.619123,
        'pickup_address' => 'Titik A',
        'destination_latitude' => -6.90389,
        'destination_longitude' => 107.61861,
        'destination_address' => 'Titik B',
        'scheduled_start_time' => '2026-07-12 10:00:00',
        'scheduled_end_time' => '2026-07-12 12:00:00',
        'bpjs_number' => '000456789',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('status', 'success');

    // Karena BPJS terverifikasi, tarif harus 0.00
    $this->assertDatabaseHas('orders', [
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'pending',
        'bpjs_status' => 'verified',
        'fare_amount' => 0.00,
    ]);
});

test('it rejects booking for conflicting schedule (anti-double-booking)', function () {
    $ambulance = new Ambulance();
    $ambulance->institution_id = $this->institution->id;
    $ambulance->plate_number = 'D 6666 DD';
    $ambulance->category = 'non-emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Buat order pertama pada jam 10:00 - 12:00
    Order::create([
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'confirmed',
        'scheduled_start_time' => '2026-07-12 10:00:00',
        'scheduled_end_time' => '2026-07-12 12:00:00',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Titik A',
    ]);

    // Coba buat order kedua pada jam 11:00 - 13:00 (Bentrok!)
    $response = $this->postJson('/api/orders/sewa', [
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulance->id,
        'pickup_latitude' => -6.917464,
        'pickup_longitude' => 107.619123,
        'pickup_address' => 'Titik A',
        'destination_latitude' => -6.90389,
        'destination_longitude' => 107.61861,
        'destination_address' => 'Titik B',
        'scheduled_start_time' => '2026-07-12 11:00:00',
        'scheduled_end_time' => '2026-07-12 13:00:00',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('status', 'conflict');
});

test('it creates event order with document upload', function () {
    Storage::fake('local');

    $file = UploadedFile::fake()->create('proposal_kegiatan.pdf', 500, 'application/pdf');

    $response = $this->postJson('/api/orders/event', [
        'user_id' => $this->customer->id,
        'pickup_latitude' => -6.917464,
        'pickup_longitude' => 107.619123,
        'pickup_address' => 'Lapangan Saparua Bandung',
        'scheduled_start_time' => '2026-07-15 08:00:00',
        'scheduled_end_time' => '2026-07-15 17:00:00',
        'document' => $file,
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('status', 'success');

    // Pastikan proposal terbuat
    $this->assertDatabaseHas('event_proposals', [
        'review_status' => 'pending',
    ]);
});

test('it allows driver to confirm and update trip status to completion', function () {
    $ambulance = new Ambulance();
    $ambulance->institution_id = $this->institution->id;
    $ambulance->plate_number = 'D 5555 EE';
    $ambulance->category = 'non-emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Buat order pending
    $order = Order::create([
        'user_id' => $this->customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'pending',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Titik A',
    ]);

    // 1. Konfirmasi Order
    $responseConfirm = $this->postJson("/api/orders/{$order->id}/confirm");
    $responseConfirm->assertStatus(200);

    expect(Order::find($order->id)->status)->toBe('confirmed');
    expect(Ambulance::find($ambulance->id)->status)->toBe('on_duty');

    // 2. Update status ke completed
    $responseStatus = $this->postJson("/api/orders/{$order->id}/status", [
        'status' => 'completed',
    ]);
    $responseStatus->assertStatus(200);

    expect(Order::find($order->id)->status)->toBe('completed');
    // Ambulans harus kembali standby setelah order completed
    expect(Ambulance::find($ambulance->id)->status)->toBe('standby');
});
