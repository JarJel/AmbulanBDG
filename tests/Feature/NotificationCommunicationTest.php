<?php

use App\Channels\SmsChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Ambulance;
use App\Models\Institution;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('it dispatches notifications via all required channels', function () {
    Mail::fake();
    Notification::fake();

    // 1. Setup Data
    $user = User::create([
        'name' => 'Adit Customer',
        'email' => 'adit@notification.com',
        'password' => bcrypt('password'),
        'phone' => '628123456789',
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Sukajadi',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1234 XX';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    $order = Order::create([
        'user_id' => $user->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan Merdeka No. 1',
        'fare_amount' => 0.00,
    ]);

    // 2. Dispatch Notification
    $user->notify(new OrderStatusUpdated($order));

    // 3. Verify via Notification Fake
    Notification::assertSentTo(
        $user,
        OrderStatusUpdated::class,
        function ($notification, $channels) {
            return in_array('database', $channels) &&
                   in_array('mail', $channels) &&
                   in_array(WhatsAppChannel::class, $channels) &&
                   in_array(SmsChannel::class, $channels);
        }
    );
});

test('it formats data correctly for each channel', function () {
    // 1. Setup Data
    $user = User::create([
        'name' => 'Adit Customer',
        'email' => 'adit_format@notification.com',
        'password' => bcrypt('password'),
        'phone' => '628123456789',
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Sukajadi',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1234 XX';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    $order = Order::create([
        'user_id' => $user->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan Merdeka No. 1',
        'fare_amount' => 0.00,
    ]);

    $notification = new OrderStatusUpdated($order);

    // 2. Test database payload
    $dbPayload = $notification->toDatabase($user);
    expect($dbPayload)->toHaveKey('order_id', $order->id);
    expect($dbPayload)->toHaveKey('order_type', 'SOS');
    expect($dbPayload)->toHaveKey('status', 'confirmed');
    expect($dbPayload['message'])->toContain('Telah Dikonfirmasi');

    // 3. Test WhatsApp payload
    $waPayload = $notification->toWhatsApp($user);
    expect($waPayload['content'])->toContain("[AmbulanBDG]");
    expect($waPayload['content'])->toContain("Telah Dikonfirmasi");

    // 4. Test SMS payload
    $smsPayload = $notification->toSms($user);
    expect($smsPayload['content'])->toContain("AmbulanBDG:");
    expect($smsPayload['content'])->toContain("Telah Dikonfirmasi");

    // 5. Test Mail rendering
    $mailPayload = $notification->toMail($user);
    expect($mailPayload->subject)->toContain("Update Pesanan Ambulans BDG");
    expect($mailPayload->greeting)->toContain("Halo, Adit Customer!");
});

test('it saves in-app notification in database notifications table', function () {
    // 1. Setup Data
    $user = User::create([
        'name' => 'Adit DB',
        'email' => 'adit_db@notification.com',
        'password' => bcrypt('password'),
        'phone' => '628123456789',
        'role' => 'customer',
    ]);

    $institution = Institution::create([
        'name' => 'Klinik Sukajadi',
        'type' => 'fasyankes',
        'verified_status' => 'verified',
    ]);

    $ambulance = new Ambulance();
    $ambulance->institution_id = $institution->id;
    $ambulance->plate_number = 'D 1234 XX';
    $ambulance->category = 'emergency';
    $ambulance->status = 'standby';
    $ambulance->current_location = DB::raw("Point(107.619123, -6.917464)");
    $ambulance->save();

    $order = Order::create([
        'user_id' => $user->id,
        'ambulance_id' => $ambulance->id,
        'order_type' => 'SOS',
        'status' => 'confirmed',
        'pickup_location' => DB::raw("Point(107.619123, -6.917464)"),
        'pickup_address' => 'Jalan Merdeka No. 1',
        'fare_amount' => 0.00,
    ]);

    // 2. Dispatch real notification (not faked) to write to DB
    $user->notify(new OrderStatusUpdated($order));

    // 3. Verify record in notifications table
    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
    ]);

    // Retrieve and verify data array
    $dbNotification = DB::table('notifications')->where('notifiable_id', $user->id)->first();
    $data = json_decode($dbNotification->data, true);
    expect($data)->toHaveKey('order_id', $order->id);
    expect($data)->toHaveKey('status', 'confirmed');
});
