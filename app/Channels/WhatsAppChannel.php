<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
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
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $messageData = $notification->toWhatsApp($notifiable);
        $phone = $notifiable->phone ?? $notifiable->routeNotificationFor('whatsapp', $notification);

        if (!$phone) {
            return;
        }

        // Mock WhatsApp Business API integration via logging
        Log::info("WhatsApp API Dispatch:", [
            'to' => $phone,
            'body' => $messageData['content'] ?? $messageData,
        ]);
    }
}
