<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveStaleGpsLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $archiveThreshold = now()->subDays(30);

        // Make sure archive table exists
        if (!Schema::hasTable('trip_tracking_logs_archive')) {
            Schema::create('trip_tracking_logs_archive', function ($table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->geometry('coordinates');
                $table->timestamp('recorded_at');
            });
        }

        // Copy records older than 30 days to archive table
        DB::statement("
            INSERT INTO trip_tracking_logs_archive (order_id, coordinates, recorded_at)
            SELECT order_id, coordinates, recorded_at
            FROM trip_tracking_logs
            WHERE recorded_at < ?
        ", [$archiveThreshold]);

        // Delete archived records from active table
        DB::table('trip_tracking_logs')
            ->where('recorded_at', '<', $archiveThreshold)
            ->delete();
    }
}
