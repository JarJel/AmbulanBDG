<?php

use App\Helpers\AuditLogger;
use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\VehicleDocument;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('it restricts access based on user role (RBAC)', function () {
    // 1. Create a Customer and a Super Admin
    $customer = User::create([
        'name' => 'Budi Customer',
        'email' => 'budi_rbac@customer.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $admin = User::create([
        'name' => 'Admin Boss',
        'email' => 'admin_rbac@boss.com',
        'password' => bcrypt('password'),
        'role' => 'super_admin',
    ]);

    // 2. Request without login -> 403
    $responseGuest = $this->getJson('/api/test-rbac-admin');
    $responseGuest->assertStatus(403);

    // 3. Request as customer -> 403
    $responseCustomer = $this->actingAs($customer)->getJson('/api/test-rbac-admin');
    $responseCustomer->assertStatus(403);

    // 4. Request as admin -> 200
    $responseAdmin = $this->actingAs($admin)->getJson('/api/test-rbac-admin');
    $responseAdmin->assertStatus(200);
    $responseAdmin->assertJsonPath('message', 'welcome admin');
});

test('it records audit logs accurately', function () {
    $admin = User::create([
        'name' => 'Admin Boss',
        'email' => 'admin_audit@boss.com',
        'password' => bcrypt('password'),
        'role' => 'super_admin',
    ]);

    AuditLogger::log($admin->id, 'verify_document', 'Admin verified STNK document.');

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'verify_document',
        'description' => 'Admin verified STNK document.',
        'ip_address' => '127.0.0.1',
    ]);
});

test('it auto suspends ambulances with expired documents or staff licenses', function () {
    // 1. Setup Institution
    $institution = Institution::create([
        'name' => 'RS Bandung Barat',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    // 2. Setup Driver with expired license
    $driver = User::create([
        'name' => 'Driver Oded',
        'email' => 'oded@driver.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
        'institution_id' => $institution->id,
    ]);

    $driverProfile = StaffProfile::create([
        'user_id' => $driver->id,
        'staff_type' => 'driver',
        'license_number' => 'SIM-123456',
        'license_expiry' => now()->subDays(2)->toDateString(), // Expired 2 days ago
        'verification_status' => 'approved',
    ]);

    // 3. Setup Ambulance in standby
    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 3333 GG';
    $ambulance->category = 'non-emergency';
    $ambulance->status = 'standby';
    $ambulance->current_driver_id = $driver->id;
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // 4. Setup Ambulance with expired STNK doc
    $ambulance2 = new Ambulance();
    $ambulance2->institution_id = $institution->id;
    $ambulance2->plate_number = 'D 2222 HH';
    $ambulance2->category = 'non-emergency';
    $ambulance2->status = 'standby';
    $ambulance2->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance2->save();

    VehicleDocument::create([
        'ambulance_id' => $ambulance2->id,
        'doc_type' => 'STNK',
        'document_path' => '/uploads/stnk.pdf',
        'verification_status' => 'approved',
        'expiry_date' => now()->subDays(5)->toDateString(), // Expired 5 days ago
    ]);

    // Ensure status is standby before command runs
    expect(Ambulance::find($ambulance->id)->status)->toBe('standby');
    expect(Ambulance::find($ambulance2->id)->status)->toBe('standby');

    // 5. Run check-expiry console command
    $exitCode = Artisan::call('app:check-document-expiry');
    expect($exitCode)->toBe(0);

    // 6. Verify both ambulances are suspended
    expect(Ambulance::find($ambulance->id)->status)->toBe('suspended');
    expect(Ambulance::find($ambulance2->id)->status)->toBe('suspended');

    // 7. Verify audit logs were written by system (user_id = null)
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => null,
        'action' => 'auto_suspend_ambulance',
    ]);
});

test('it enforces multi-tenant scoping policy for institution admins', function () {
    // 1. Create two Institutions
    $instA = Institution::create([
        'name' => 'Inst A',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $instB = Institution::create([
        'name' => 'Inst B',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    // 2. Create two institution_admin users
    $adminA = User::create([
        'name' => 'Admin A',
        'email' => 'adminA@instA.com',
        'password' => bcrypt('password'),
        'role' => 'institution_admin',
        'institution_id' => $instA->id,
    ]);

    $adminB = User::create([
        'name' => 'Admin B',
        'email' => 'adminB@instB.com',
        'password' => bcrypt('password'),
        'role' => 'institution_admin',
        'institution_id' => $instB->id,
    ]);

    // 3. Create an Ambulance for Inst B
    $ambulanceB = new Ambulance();
    $ambulanceB->institution_id = $instB->id;
    $ambulanceB->plate_number = 'D 9999 BB';
    $ambulanceB->category = 'emergency';
    $ambulanceB->status = 'standby';
    $ambulanceB->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulanceB->save();

    // 4. Admin A tries to manage Ambulance B -> 403
    $response = $this->actingAs($adminA)->postJson("/api/admin/ambulances/{$ambulanceB->id}/manage", [
        'status' => 'offline',
    ]);
    $response->assertStatus(403);

    // 5. Admin B manages Ambulance B -> 200
    $response = $this->actingAs($adminB)->postJson("/api/admin/ambulances/{$ambulanceB->id}/manage", [
        'status' => 'offline',
    ]);
    $response->assertStatus(200);

    // 6. Verify Document Scoping
    $docB = VehicleDocument::create([
        'ambulance_id' => $ambulanceB->id,
        'doc_type' => 'STNK',
        'document_path' => '/uploads/stnk.pdf',
        'verification_status' => 'pending',
        'expiry_date' => now()->addYear()->toDateString(),
    ]);

    $response = $this->actingAs($adminA)->postJson("/api/admin/documents/{$docB->id}/verify", [
        'verification_status' => 'approved',
    ]);
    $response->assertStatus(403);

    $response = $this->actingAs($adminB)->postJson("/api/admin/documents/{$docB->id}/verify", [
        'verification_status' => 'approved',
    ]);
    $response->assertStatus(200);

    // 7. Verify Staff Profile Scoping
    $driverB = User::create([
        'name' => 'Driver B',
        'email' => 'driverB@instB.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
        'institution_id' => $instB->id,
    ]);

    $staffB = StaffProfile::create([
        'user_id' => $driverB->id,
        'staff_type' => 'driver',
        'license_number' => 'SIM-B',
        'license_expiry' => now()->addYear()->toDateString(),
        'verification_status' => 'pending',
    ]);

    $response = $this->actingAs($adminA)->postJson("/api/admin/staff/{$staffB->id}/verify", [
        'verification_status' => 'approved',
    ]);
    $response->assertStatus(403);

    $response = $this->actingAs($adminB)->postJson("/api/admin/staff/{$staffB->id}/verify", [
        'verification_status' => 'approved',
    ]);
    $response->assertStatus(200);

    // 8. Verify Event Proposal Scoping
    $customer = User::create([
        'name' => 'Customer',
        'email' => 'cust@gmail.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $order = \App\Models\Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => null,
        'order_type' => 'event',
        'status' => 'pending',
        'scheduled_start_time' => now()->addDays(5)->format('Y-m-d H:i:s'),
        'scheduled_end_time' => now()->addDays(5)->addHours(12)->format('Y-m-d H:i:s'),
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Lap. Gasibu',
        'fare_amount' => 1500000.00,
    ]);

    $proposal = \App\Models\EventProposal::create([
        'order_id' => $order->id,
        'document_path' => '/uploads/event_proposal.pdf',
        'review_status' => 'pending',
    ]);

    // Admin A tries to approve proposal by assigning Ambulance B (owned by Inst B) -> 403
    $response = $this->actingAs($adminA)->postJson("/api/admin/event-proposals/{$proposal->id}/verify", [
        'review_status' => 'approved',
        'ambulance_id' => $ambulanceB->id,
    ]);
    $response->assertStatus(403);

    // Admin B approves proposal by assigning Ambulance B -> 200
    $response = $this->actingAs($adminB)->postJson("/api/admin/event-proposals/{$proposal->id}/verify", [
        'review_status' => 'approved',
        'ambulance_id' => $ambulanceB->id,
    ]);
    $response->assertStatus(200);
});

test('it runs upcoming schedule reminders job and behaves idempotently', function () {
    Notification::fake();

    $inst = Institution::create([
        'name' => 'Inst C',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $customer = User::create([
        'name' => 'Customer C',
        'email' => 'custC@gmail.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
    ]);

    $driver = User::create([
        'name' => 'Driver C',
        'email' => 'driverC@instC.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
        'institution_id' => $inst->id,
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $inst->id;
    $ambulance->plate_number = 'D 7777 CC';
    $ambulance->category = 'non-emergency';
    $ambulance->status = 'standby';
    $ambulance->current_driver_id = $driver->id;
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // 1. Order scheduled for tomorrow, reminder not sent
    $orderTomorrow = \App\Models\Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'confirmed',
        'scheduled_start_time' => now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i:s'),
        'scheduled_end_time' => now()->addDay()->setHour(12)->setMinute(0)->format('Y-m-d H:i:s'),
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Pickup Address',
        'fare_amount' => 150000.00,
    ]);

    // 2. Order scheduled for tomorrow, reminder ALREADY sent
    $orderSentAlready = \App\Models\Order::create([
        'user_id' => $customer->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'sewa',
        'status' => 'confirmed',
        'scheduled_start_time' => now()->addDay()->setHour(14)->setMinute(0)->format('Y-m-d H:i:s'),
        'scheduled_end_time' => now()->addDay()->setHour(16)->setMinute(0)->format('Y-m-d H:i:s'),
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Pickup Address',
        'fare_amount' => 150000.00,
        'reminder_sent_at' => now()->subHours(2),
    ]);

    // Run job
    (new \App\Jobs\SendUpcomingScheduleReminders())->handle();

    // Assert notification sent for tomorrow's unsent order
    Notification::assertSentTo($customer, \App\Notifications\UpcomingScheduleReminder::class, function ($notification) use ($orderTomorrow, $customer) {
        return $notification->toDatabase($customer)['order_id'] === $orderTomorrow->id;
    });

    Notification::assertSentTo($driver, \App\Notifications\UpcomingScheduleReminder::class, function ($notification) use ($orderTomorrow, $driver) {
        return $notification->toDatabase($driver)['order_id'] === $orderTomorrow->id;
    });

    // Assert no notification sent for already sent order
    Notification::assertNotSentTo($customer, \App\Notifications\UpcomingScheduleReminder::class, function ($notification) use ($orderSentAlready, $customer) {
        return $notification->toDatabase($customer)['order_id'] === $orderSentAlready->id;
    });

    // Assert reminder_sent_at updated
    expect(\App\Models\Order::find($orderTomorrow->id)->reminder_sent_at)->not->toBeNull();
});

test('it runs check document expiry job and alerts admins', function () {
    Notification::fake();

    $inst = Institution::create([
        'name' => 'RS Cihampelas',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $admin = User::create([
        'name' => 'Admin RS',
        'email' => 'admin_rs@cihampelas.com',
        'password' => bcrypt('password'),
        'role' => 'institution_admin',
        'institution_id' => $inst->id,
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $inst->id;
    $ambulance->plate_number = 'D 4444 EX';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    // Create document expiring in 10 days
    $doc = VehicleDocument::create([
        'ambulance_id' => $ambulance->id,
        'doc_type' => 'STNK',
        'document_path' => '/uploads/stnk.pdf',
        'verification_status' => 'approved',
        'expiry_date' => now()->addDays(10)->toDateString(),
    ]);

    // Create staff driver expiring in 15 days
    $driver = User::create([
        'name' => 'Driver Cepat',
        'email' => 'cepat@driver.com',
        'password' => bcrypt('password'),
        'role' => 'driver',
        'institution_id' => $inst->id,
    ]);

    $staff = StaffProfile::create([
        'user_id' => $driver->id,
        'staff_type' => 'driver',
        'license_number' => 'SIM-1111',
        'license_expiry' => now()->addDays(15)->toDateString(),
        'verification_status' => 'approved',
    ]);

    // Run job
    (new \App\Jobs\CheckExpiringDocuments())->handle();

    // Assert alerts sent to the admin
    Notification::assertSentTo($admin, \App\Notifications\DocumentExpiringAlert::class);
    Notification::assertSentTo($admin, \App\Notifications\StaffLicenseExpiringAlert::class);

    // Assert alert_sent_at populated
    expect(VehicleDocument::find($doc->id)->alert_sent_at)->not->toBeNull();
    expect(StaffProfile::find($staff->id)->alert_sent_at)->not->toBeNull();
});
