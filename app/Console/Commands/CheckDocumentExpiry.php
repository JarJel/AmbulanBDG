<?php

namespace App\Console\Commands;

use App\Helpers\AuditLogger;
use App\Models\Ambulance;
use App\Models\StaffProfile;
use App\Models\VehicleDocument;
use Illuminate\Console\Command;

class CheckDocumentExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-document-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek dokumen kendaraan (STNK/KIR) dan lisensi staf (SIM/STR) yang kedaluwarsa lalu menangguhkan armada terkait.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->toDateString();
        $this->info("Memulai pengecekan dokumen kedaluwarsa untuk tanggal: {$today}...");

        (new \App\Jobs\CheckExpiringDocuments())->handle();

        $this->info("Pengecekan selesai.");
        return 0;
    }
}
