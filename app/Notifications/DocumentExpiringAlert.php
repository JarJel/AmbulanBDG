<?php

namespace App\Notifications;

use App\Models\VehicleDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiringAlert extends Notification
{
    use Queueable;

    protected $doc;

    /**
     * Create a new notification instance.
     */
    public function __construct(VehicleDocument $doc)
    {
        $this->doc = $doc;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $plate = $this->doc->ambulance ? $this->doc->ambulance->plate_number : '-';
        $expiry = $this->doc->expiry_date ? $this->doc->expiry_date->toDateString() : '-';
        return (new MailMessage)
            ->subject("Pemberitahuan Kadaluwarsa Dokumen Armada - {$this->doc->doc_type}")
            ->greeting("Halo, Admin!")
            ->line("Dokumen {$this->doc->doc_type} untuk armada dengan plat nomor {$plate} akan kedaluwarsa pada {$expiry}.")
            ->line("Harap segera lakukan perpanjangan dokumen untuk menghindari penangguhan armada otomatis.")
            ->action('Kelola Dokumen', url("/admin/documents/{$this->doc->id}"));
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $plate = $this->doc->ambulance ? $this->doc->ambulance->plate_number : '-';
        $expiry = $this->doc->expiry_date ? $this->doc->expiry_date->toDateString() : '-';
        return [
            'document_id' => $this->doc->id,
            'ambulance_id' => $this->doc->ambulance_id,
            'doc_type' => $this->doc->doc_type,
            'message' => "Pemberitahuan: Dokumen {$this->doc->doc_type} untuk armada {$plate} akan kedaluwarsa pada {$expiry}.",
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
