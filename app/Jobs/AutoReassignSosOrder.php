<?php

namespace App\Jobs;

use App\Models\Ambulance;
use App\Models\Order;
use App\Models\SosLog;
use App\Models\TripStatusHistory;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Services\SosDispatchService;
use App\Events\SosEscalationFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AutoReassignSosOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId,
        public int $previousAmbulanceId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SosDispatchService $dispatcher): void
    {
        $order = Order::find($this->orderId);

        // If order was already accepted or cancelled, do nothing
        if (!$order || $order->status !== 'awaiting_driver_response' || $order->ambulance_id !== $this->previousAmbulanceId) {
            return;
        }

        DB::transaction(function () use ($order, $dispatcher) {
            // Re-fetch order under transaction lock
            $order = Order::where('id', $this->orderId)->lockForUpdate()->first();
            if (!$order || $order->status !== 'awaiting_driver_response' || $order->ambulance_id !== $this->previousAmbulanceId) {
                return;
            }

            // Release the previous ambulance back to standby
            $prevAmbulance = Ambulance::find($this->previousAmbulanceId);
            if ($prevAmbulance && $prevAmbulance->status === 'dispatching') {
                $prevAmbulance->update(['status' => 'standby']);
            }

            // Update the SOS log to indicate escalation
            SosLog::where('order_id', $order->id)
                ->where('ambulance_id', $this->previousAmbulanceId)
                ->update([
                    'escalated' => true,
                    'response_time_seconds' => 30, // SLA limit
                ]);

            // Track this history change
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'awaiting_driver_response',
                'changed_at' => now(),
            ]);

            // Exclude the previous ambulance and try dispatching to the next closest
            $newAmbulance = $dispatcher->dispatch($order, [$this->previousAmbulanceId]);

            if (!$newAmbulance) {
                // Escalation failed: No other ambulance is available
                $order->update(['status' => 'no_ambulance_available']);

                TripStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'no_ambulance_available',
                    'changed_at' => now(),
                ]);

                // Fire escalation failed event
                event(new SosEscalationFailed($order));

                // Send notification to customer
                $user = User::find($order->user_id);
                if ($user) {
                    $user->notify(new OrderStatusUpdated($order));
                }
            }
        });
    }
}
