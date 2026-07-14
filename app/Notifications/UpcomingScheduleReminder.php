<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingScheduleReminder extends Notification
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsAppChannel::class, SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $formattedTime = $this->order->scheduled_start_time ? $this->order->scheduled_start_time->format('Y-m-d H:i:s') : '-';
        return (new MailMessage)
            ->subject("Pengingat Jadwal Ambulans BDG - (#{$this->order->id})")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Ini adalah pengingat bahwa pesanan ambulans Anda (#{$this->order->id}) dijadwalkan untuk besok:")
            ->line("Waktu Mulai: {$formattedTime}")
            ->line("Alamat Penjemputan: {$this->order->pickup_address}")
            ->action('Lihat Detail Pesanan', url("/orders/{$this->order->id}"))
            ->line('Terima kasih telah menggunakan layanan AmbulanBDG.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $timeStr = $this->order->scheduled_start_time ? $this->order->scheduled_start_time->format('H:i') : '-';
        return [
            'order_id' => $this->order->id,
            'order_type' => $this->order->order_type,
            'message' => "Pengingat: Pesanan {$this->order->order_type} Anda dijadwalkan besok pada pukul {$timeStr}.",
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $timeStr = $this->order->scheduled_start_time ? $this->order->scheduled_start_time->format('H:i') : '-';
        $message = "[AmbulanBDG] Pengingat: Pesanan ambulans #{$this->order->id} Anda dijadwalkan besok pukul {$timeStr}. Penjemputan di {$this->order->pickup_address}.";

        return [
            'content' => $message,
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $timeStr = $this->order->scheduled_start_time ? $this->order->scheduled_start_time->format('H:i') : '-';
        $message = "AmbulanBDG: Pengingat order #{$this->order->id} Anda dijadwalkan besok pukul {$timeStr}.";

        return [
            'content' => $message,
        ];
    }
}
