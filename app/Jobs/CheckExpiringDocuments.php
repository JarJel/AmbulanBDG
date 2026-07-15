<?php

namespace App\Jobs;

use App\Helpers\AuditLogger;
use App\Models\Ambulance;
use App\Models\StaffProfile;
use App\Models\VehicleDocument;
use App\Notifications\DocumentExpiringAlert;
use App\Notifications\StaffLicenseExpiringAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiringDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->checkVehicleDocuments();
        $this->checkStaffLicenses();
        $this->checkPolymorphicDocuments();
    }

    /**
     * Check new polymorphic documents expiring within 30 days.
     */
    private function checkPolymorphicDocuments(): void
    {
        $limitDate = now()->addDays(30)->toDateString();
        $today = now()->toDateString();
        
        $expiringDocs = \App\Models\DocumentVerification::where('expiry_date', '<=', $limitDate)
            ->where('expiry_date', '>', $today)
            ->where('status', 'verified')
            ->get();
            
        foreach ($expiringDocs as $doc) {
            $admins = collect();
            if ($doc->verifiable_type === \App\Models\Ambulance::class) {
                $ambulance = \App\Models\Ambulance::find($doc->verifiable_id);
                if ($ambulance && $ambulance->institution) {
                    $admins = $ambulance->institution->admins ?? collect();
                }
            } else if ($doc->verifiable_type === \App\Models\User::class) {
                $u = \App\Models\User::find($doc->verifiable_id);
                if ($u && $u->institution) {
                    $admins = $u->institution->admins ?? collect();
                }
            }
            
            foreach ($admins as $admin) {
                // Broadcast warning using Reverb
                try {
                    broadcast(new \App\Events\SlaBreached(new \App\Models\Order(), "Dokumen {$doc->document_type} (ID Verifiable: {$doc->verifiable_id}) mendekati tanggal kedaluwarsa dalam 30 hari!"));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Gagal mengirim broadcast dokumen kedaluwarsa: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Check vehicle documents approaching expiration and already expired.
     */
    private function checkVehicleDocuments(): void
    {
        $today = now()->toDateString();
        $limitDate = now()->addDays(30)->toDateString();

        // 1. Proactive alerts for documents expiring in <= 30 days
        $expiringSoon = VehicleDocument::where('expiry_date', '<=', $limitDate)
            ->where('expiry_date', '>', $today)
            ->whereNull('alert_sent_at')
            ->with('ambulance.institution.admins')
            ->get();

        foreach ($expiringSoon as $doc) {
            if ($doc->ambulance && $doc->ambulance->institution) {
                $doc->ambulance->institution->admins->each(
                    fn ($admin) => $admin->notify(new DocumentExpiringAlert($doc))
                );
            }
            $doc->update(['alert_sent_at' => now()]);
        }

        // 2. Auto-suspend ambulances with expired documents
        $expired = VehicleDocument::where('expiry_date', '<', $today)
            ->with('ambulance')
            ->get();

        foreach ($expired as $doc) {
            if ($doc->ambulance && $doc->ambulance->status !== 'suspended') {
                $doc->ambulance->status = 'suspended';
                $doc->ambulance->save();

                $expiryStr = $doc->expiry_date instanceof \Carbon\Carbon 
                    ? $doc->expiry_date->toDateString() 
                    : $doc->expiry_date;

                AuditLogger::log(
                    null, // Sistem
                    'auto_suspend_ambulance',
                    "Ambulans {$doc->ambulance->plate_number} ditangguhkan otomatis karena dokumen {$doc->doc_type} kedaluwarsa pada {$expiryStr}."
                );
            }
        }
    }

    /**
     * Check staff licenses approaching expiration and already expired.
     */
    private function checkStaffLicenses(): void
    {
        $today = now()->toDateString();
        $limitDate = now()->addDays(30)->toDateString();

        // 1. Proactive alerts for licenses expiring in <= 30 days
        $expiringSoon = StaffProfile::where('license_expiry', '<=', $limitDate)
            ->where('license_expiry', '>', $today)
            ->whereNull('alert_sent_at')
            ->with('user.institution.admins')
            ->get();

        foreach ($expiringSoon as $staff) {
            if ($staff->user && $staff->user->institution) {
                $staff->user->institution->admins->each(
                    fn ($admin) => $admin->notify(new StaffLicenseExpiringAlert($staff))
                );
            }
            $staff->update(['alert_sent_at' => now()]);
        }

        // 2. Auto-suspend ambulances assigned to expired staff
        $expired = StaffProfile::where('license_expiry', '<', $today)->get();

        foreach ($expired as $staff) {
            $ambulances = Ambulance::where(function ($q) use ($staff) {
                $q->where('current_driver_id', $staff->user_id)
                  ->orWhere('current_nurse_id', $staff->user_id);
            })
            ->where('status', '!=', 'suspended')
            ->get();

            foreach ($ambulances as $ambulance) {
                $ambulance->status = 'suspended';
                $ambulance->save();

                $expiryStr = $staff->license_expiry instanceof \Carbon\Carbon 
                    ? $staff->license_expiry->toDateString() 
                    : $staff->license_expiry;

                AuditLogger::log(
                    null, // Sistem
                    'auto_suspend_ambulance',
                    "Ambulans {$ambulance->plate_number} ditangguhkan otomatis karena lisensi {$staff->staff_type} (User ID: {$staff->user_id}) kedaluwarsa pada {$expiryStr}."
                );
            }
        }
    }
}
