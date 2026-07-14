<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $messageData = $notification->toSms($notifiable);
        $phone = $notifiable->phone ?? $notifiable->routeNotificationFor('sms', $notification);

        if (!$phone) {
            return;
        }

        // Mock SMS Gateway integration via logging
        Log::info("SMS Gateway Dispatch:", [
            'to' => $phone,
            'body' => $messageData['content'] ?? $messageData,
        ]);
    }
}
