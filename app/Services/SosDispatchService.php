<?php

namespace App\Services;

use App\Models\Ambulance;
use App\Models\Order;
use App\Models\SosLog;
use App\Models\TripStatusHistory;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Jobs\AutoReassignSosOrder;
use Illuminate\Support\Facades\DB;

class SosDispatchService
{
    /**
     * Dispatch an SOS order to the nearest standby ambulance.
     *
     * @param Order $order
     * @param array $excludeIds
     * @return Ambulance|null
     */
    public function dispatch(Order $order, array $excludeIds = [], ?int $ambulanceId = null): ?Ambulance
    {
        $coords = DB::selectOne("SELECT ST_X(pickup_location) as lng, ST_Y(pickup_location) as lat FROM orders WHERE id = ?", [$order->id]);
        if (!$coords) {
            return null;
        }
        $lng = $coords->lng;
        $lat = $coords->lat;

        return DB::transaction(function () use ($order, $excludeIds, $lng, $lat, $ambulanceId) {
            $ambulance = null;

            // If a specific ambulance is requested, lock and query it
            if ($ambulanceId) {
                $ambulance = Ambulance::query()
                    ->where('id', $ambulanceId)
                    ->where('status', 'standby')
                    ->where('is_free_service_eligible', true)
                    ->lockForUpdate()
                    ->first();
            }

            // Fallback to nearest standby ambulance within 10 km if not found
            if (!$ambulance) {
                $ambulance = Ambulance::query()
                    ->where('status', 'standby')
                    ->where('is_free_service_eligible', true)
                    ->when($excludeIds, fn ($q) => $q->whereNotIn('id', $excludeIds))
                    ->select('*')
                    ->selectRaw('ST_Distance_Sphere(current_location, Point(?, ?)) AS distance', [$lng, $lat])
                    ->having('distance', '<=', 10000) // 10 KM limit
                    ->orderBy('distance', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$ambulance) {
                return null;
            }

            // Update ambulance status to dispatching
            $ambulance->update(['status' => 'dispatching']);

            // Prioritas SOS: bebaskan jadwal rental terjadwal (sewa) yang bentrok dalam 2 jam ke depan
            Order::where('ambulance_id', $ambulance->id)
                ->where('order_type', 'sewa')
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('scheduled_start_time', '<=', now()->addHours(2))
                ->update([
                    'ambulance_id' => null,
                    'status' => 'pending' // kembali ke pool pencarian
                ]);

            // Update order status to awaiting_driver_response
            $order->update([
                'ambulance_id' => $ambulance->id,
                'status' => 'awaiting_driver_response',
            ]);

            // Create/Update SOS Log
            SosLog::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'ambulance_id' => $ambulance->id,
                    'dispatched_at' => now(),
                    'escalated' => false,
                    'response_time_seconds' => null,
                ]
            );

            // Log status history
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'awaiting_driver_response',
                'changed_at' => now(),
            ]);

            // Send Notification to Customer
            if ($order->user_id) {
                $user = User::find($order->user_id);
                if ($user) {
                    $user->notify(new OrderStatusUpdated($order));
                }
            } else if ($order->guest_phone) {
                // Fallback: Kirim notifikasi simulasi via SMS/WhatsApp Gateway
                \Illuminate\Support\Facades\Log::info("SMS/WhatsApp Gateway to guest phone {$order->guest_phone}: Panggilan darurat diterima. Ambulans sedang meluncur! Lacak di: " . route('orders.guest.tracking', [$order->id, $order->guest_session_token]));
            }

            // Dispatch the delayed job (30 seconds SLA)
            AutoReassignSosOrder::dispatch($order->id, $ambulance->id)
                ->delay(now()->addSeconds(30));

            return $ambulance;
        }, 3); // retry 3 times on deadlock
    }
}
