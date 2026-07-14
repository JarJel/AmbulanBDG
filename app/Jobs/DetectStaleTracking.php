<?php

namespace App\Jobs;

use App\Models\Order;
use App\Events\TrackingSignalLost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectStaleTracking implements ShouldQueue
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
        $staleThreshold = now()->subMinutes(2);

        // Find active orders where the assigned ambulance's last_location_at is older than 2 minutes
        // or has never been updated and the order was created more than 2 minutes ago.
        $activeOrders = Order::whereIn('status', ['on_the_way', 'transporting'])
            ->where(function ($q) use ($staleThreshold) {
                $q->whereHas('ambulance', function ($query) use ($staleThreshold) {
                    $query->where('last_location_at', '<', $staleThreshold);
                })
                ->orWhere(function ($query) use ($staleThreshold) {
                    $query->whereHas('ambulance', function ($sub) {
                        $sub->whereNull('last_location_at');
                    })
                    ->where('created_at', '<', $staleThreshold);
                });
            })
            ->get();

        foreach ($activeOrders as $order) {
            if (!$order->tracking_lost_notified_at) {
                // Set the notification timestamp
                $order->update(['tracking_lost_notified_at' => now()]);

                // Broadcast tracking signal lost event to user
                broadcast(new TrackingSignalLost($order));
            }
        }
    }
}
