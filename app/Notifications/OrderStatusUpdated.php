<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Channels\WhatsAppChannel;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
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
        $statusText = $this->getStatusText();
        
        return (new MailMessage)
            ->subject("Update Pesanan Ambulans BDG - {$this->order->order_type} (#{$this->order->id})")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Status pesanan ambulans Anda (#{$this->order->id}) saat ini telah diperbarui menjadi: **{$statusText}**.")
            ->line("Jenis Pemesanan: " . strtoupper($this->order->order_type))
            ->line("Alamat Penjemputan: {$this->order->pickup_address}")
            ->action('Pantau Pesanan Anda', url("/orders/{$this->order->id}"))
            ->line('Terima kasih telah menggunakan layanan AmbulanBDG.');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_type' => $this->order->order_type,
            'status' => $this->order->status,
            'message' => "Pesanan {$this->order->order_type} Anda saat ini berstatus: " . $this->getStatusText(),
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): array
    {
        $statusText = $this->getStatusText();
        $message = "[AmbulanBDG] Halo {$notifiable->name}, status pesanan ambulans #{$this->order->id} ({$this->order->order_type}) Anda telah diperbarui menjadi: *{$statusText}*. Alamat penjemputan: {$this->order->pickup_address}. Pantau lokasi real-time di aplikasi.";

        return [
            'content' => $message,
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $statusText = $this->getStatusText();
        $message = "AmbulanBDG: Halo {$notifiable->name}, status order #{$this->order->id} Anda: {$statusText}. Penjemputan di {$this->order->pickup_address}.";

        return [
            'content' => $message,
        ];
    }

    /**
     * Get the human readable status text.
     */
    protected function getStatusText(): string
    {
        return match ($this->order->status) {
            'pending' => 'Menunggu Konfirmasi',
            'awaiting_driver_response' => 'Menunggu Respon Driver',
            'no_ambulance_available' => 'Tidak Ada Ambulans Tersedia',
            'confirmed' => 'Telah Dikonfirmasi (Ambulans sedang menuju lokasi Anda)',
            'on_the_way' => 'Ambulans Sedang di Perjalanan ke Lokasi',
            'at_pickup' => 'Ambulans Telah Sampai di Lokasi Penjemputan',
            'heading_to_destination' => 'Sedang Menuju Rumah Sakit/Fasyankes Tujuan',
            'completed' => 'Perjalanan Selesai',
            'cancelled' => 'Dibatalkan',
            default => $this->order->status,
        };
    }
}
