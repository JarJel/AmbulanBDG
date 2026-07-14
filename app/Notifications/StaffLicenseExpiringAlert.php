<?php

namespace App\Notifications;

use App\Models\StaffProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffLicenseExpiringAlert extends Notification
{
    use Queueable;

    protected $staff;

    /**
     * Create a new notification instance.
     */
    public function __construct(StaffProfile $staff)
    {
        $this->staff = $staff;
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
        $staffName = $this->staff->user ? $this->staff->user->name : '-';
        $expiry = $this->staff->license_expiry ? $this->staff->license_expiry->toDateString() : '-';
        return (new MailMessage)
            ->subject("Pemberitahuan Kadaluwarsa Lisensi Staf - {$this->staff->staff_type}")
            ->greeting("Halo, Admin!")
            ->line("Lisensi {$this->staff->staff_type} untuk staf bernama {$staffName} akan kedaluwarsa pada {$expiry}.")
            ->line("Harap segera koordinasikan untuk perpanjangan lisensi agar staf tetap dapat ditugaskan.")
            ->action('Kelola Staf', url("/admin/staff/{$this->staff->id}"));
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $staffName = $this->staff->user ? $this->staff->user->name : '-';
        $expiry = $this->staff->license_expiry ? $this->staff->license_expiry->toDateString() : '-';
        return [
            'staff_profile_id' => $this->staff->id,
            'user_id' => $this->staff->user_id,
            'staff_type' => $this->staff->staff_type,
            'message' => "Pemberitahuan: Lisensi {$this->staff->staff_type} ({$staffName}) akan kedaluwarsa pada {$expiry}.",
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}
