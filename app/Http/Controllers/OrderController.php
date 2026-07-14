<?php

namespace App\Http\Controllers;

use App\Events\AmbulanceLocationUpdated;
use App\Models\Ambulance;
use App\Models\EventProposal;
use App\Models\Order;
use App\Models\SosLog;
use App\Models\TripStatusHistory;
use App\Models\TripTrackingLog;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Helper untuk memuat order beserta relasi dan koordinat ambulans
     */
    private function loadOrderWithCoords($id)
    {
        $order = Order::with([
            'ambulance.institution',
            'ambulance.currentDriver',
            'ambulance.currentNurse'
        ])->findOrFail($id);

        // Inject order pickup coordinates
        $orderCoords = DB::selectOne("SELECT ST_X(pickup_location) as lng, ST_Y(pickup_location) as lat FROM orders WHERE id = ?", [$id]);
        if ($orderCoords) {
            $order->pickup_latitude = $orderCoords->lat;
            $order->pickup_longitude = $orderCoords->lng;
        }

        // Inject order destination coordinates if exists
        $destCoords = DB::selectOne("SELECT ST_X(destination_location) as lng, ST_Y(destination_location) as lat FROM orders WHERE id = ?", [$id]);
        if ($destCoords) {
            $order->destination_latitude = $destCoords->lat;
            $order->destination_longitude = $destCoords->lng;
        }

        if ($order->ambulance) {
            $coords = DB::selectOne("SELECT ST_X(current_location) as lng, ST_Y(current_location) as lat FROM ambulances WHERE id = ?", [$order->ambulance_id]);
            if ($coords) {
                $order->ambulance->latitude = $coords->lat;
                $order->ambulance->longitude = $coords->lng;
            }
        }

        return $order;
    }

    /**
     * Mendapatkan detail order tertentu beserta relasinya.
     */
    public function getOrder($id)
    {
        $order = $this->loadOrderWithCoords($id);

        return response()->json([
            'status' => 'success',
            'order' => $order,
        ]);
    }

    /**
     * Jalur SOS/Darurat - Mencari ambulans terdekat secara otomatis.
     */
    public function postSosOrder(Request $request, \App\Services\SosDispatchService $dispatcher)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'pickup_address' => 'required|string',
            'ambulance_id' => 'nullable|exists:ambulances,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude = $request->pickup_latitude;
        $longitude = $request->pickup_longitude;

        // 1. Create a pending SOS order first (so it gets DB spatial Point and ID)
        $order = Order::create([
            'user_id' => $request->user_id,
            'ambulance_id' => null,
            'order_type' => 'SOS',
            'status' => 'pending',
            'pickup_location' => DB::raw("Point({$longitude}, {$latitude})"),
            'pickup_address' => $request->pickup_address,
            'fare_amount' => 0.00,
        ]);

        // 2. Dispatch using the service with locked query, passing optional selected ambulance_id
        $ambulance = $dispatcher->dispatch($order, [], $request->ambulance_id);

        if (!$ambulance) {
            $order->update(['status' => 'no_ambulance_available']);

            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'no_ambulance_available',
                'changed_at' => now(),
            ]);

            return response()->json([
                'status' => 'no_ambulance_available',
                'message' => 'Tidak ada ambulans standby di radar. Silakan hubungi 112 atau 119.',
                'fallback_number' => '112',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Panggilan darurat terkirim. Menunggu konfirmasi driver.',
            'order' => $this->loadOrderWithCoords($order->id),
        ], 201);
    }

    /**
     * Jalur Sewa/Non-Darurat - Sewa terjadwal dengan Fare Calculator & Anti-Double-Booking.
     */
    public function postSewaOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'ambulance_id' => 'required|exists:ambulances,id',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'pickup_address' => 'required|string',
            'destination_latitude' => 'required|numeric',
            'destination_longitude' => 'required|numeric',
            'destination_address' => 'required|string',
            'scheduled_start_time' => 'required|date_format:Y-m-d H:i:s',
            'scheduled_end_time' => 'required|date_format:Y-m-d H:i:s|after:scheduled_start_time',
            'bpjs_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cek Anti-Double-Booking pada jadwal kalender ambulans tersebut
        $isBooked = Order::where('ambulance_id', $request->ambulance_id)
            ->whereIn('status', ['pending', 'confirmed', 'on_the_way', 'arrived', 'transporting'])
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('scheduled_start_time', '<=', $request->scheduled_start_time)
                      ->where('scheduled_end_time', '>=', $request->scheduled_start_time);
                })->orWhere(function ($q) use ($request) {
                    $q->where('scheduled_start_time', '<=', $request->scheduled_end_time)
                      ->where('scheduled_end_time', '>=', $request->scheduled_end_time);
                })->orWhere(function ($q) use ($request) {
                    $q->where('scheduled_start_time', '>=', $request->scheduled_start_time)
                      ->where('scheduled_end_time', '<=', $request->scheduled_end_time);
                });
            })
            ->exists();

        if ($isBooked) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'Ambulans sudah memiliki jadwal lain pada rentang waktu tersebut.',
            ], 422);
        }

        // Kalkulasi jarak menggunakan formula Haversine (jarak lurus dalam KM)
        $latFrom = deg2rad($request->pickup_latitude);
        $lonFrom = deg2rad($request->pickup_longitude);
        $latTo = deg2rad($request->destination_latitude);
        $lonTo = deg2rad($request->destination_longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
          cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        $distance = $angle * 6371; // Jarak dalam KM

        // Hitung tarif menggunakan konfigurasi tarif dinamis armada ambulans
        $ambulance = Ambulance::findOrFail($request->ambulance_id);
        $baseFare = $ambulance->base_fare ?? 100000.00;
        $pricePerKm = $ambulance->price_per_km ?? 15000.00;
        $fare = $baseFare + ($distance * $pricePerKm);

        // e-Verifikasi BPJS (Mocking Logic)
        $bpjsStatus = null;
        if ($request->bpjs_number) {
            // Jika nomor kartu BPJS dimulai dengan '000', maka terverifikasi (gratis)
            $bpjsStatus = str_starts_with($request->bpjs_number, '000') ? 'verified' : 'rejected';
            if ($bpjsStatus === 'verified') {
                $fare = 0.00;
            }
        }

        return DB::transaction(function () use ($request, $fare, $bpjsStatus) {
            $order = Order::create([
                'user_id' => $request->user_id,
                'ambulance_id' => $request->ambulance_id,
                'order_type' => 'sewa',
                'status' => 'pending', // Menunggu konfirmasi manual
                'scheduled_start_time' => $request->scheduled_start_time,
                'scheduled_end_time' => $request->scheduled_end_time,
                'pickup_location' => DB::raw("Point({$request->pickup_longitude}, {$request->pickup_latitude})"),
                'pickup_address' => $request->pickup_address,
                'destination_location' => DB::raw("Point({$request->destination_longitude}, {$request->destination_latitude})"),
                'destination_address' => $request->destination_address,
                'fare_amount' => $fare,
                'bpjs_number' => $request->bpjs_number,
                'bpjs_status' => $bpjsStatus,
            ]);

            // Log status pertama
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'changed_at' => now(),
            ]);

            // Kirim Notifikasi
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pesanan sewa terjadwal berhasil dibuat (Menunggu Konfirmasi).',
                'order' => Order::find($order->id),
            ], 201);
        });
    }

    /**
     * Jalur Event/Instansi - Form proposal event (PDF).
     */
    public function postEventOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'pickup_address' => 'required|string',
            'scheduled_start_time' => 'required|date_format:Y-m-d H:i:s',
            'scheduled_end_time' => 'required|date_format:Y-m-d H:i:s|after:scheduled_start_time',
            'document' => 'required|file|mimes:pdf|max:10240', // Max 10MB PDF
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Mock upload path
        $documentPath = '/uploads/proposals/proposal_' . time() . '.pdf';
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('proposals');
        }

        // Estimasi biaya flat untuk siaga event (Rp 1.500.000)
        $fare = 1500000.00;

        return DB::transaction(function () use ($request, $documentPath, $fare) {
            // Buat order dengan ambulance_id = null (nanti ditunjuk oleh admin fasyankes setelah proposal disetujui)
            $order = Order::create([
                'user_id' => $request->user_id,
                'ambulance_id' => null,
                'order_type' => 'event',
                'status' => 'pending',
                'scheduled_start_time' => $request->scheduled_start_time,
                'scheduled_end_time' => $request->scheduled_end_time,
                'pickup_location' => DB::raw("Point({$request->pickup_longitude}, {$request->pickup_latitude})"),
                'pickup_address' => $request->pickup_address,
                'fare_amount' => $fare,
            ]);

            // Simpan berkas proposal
            EventProposal::create([
                'order_id' => $order->id,
                'document_path' => $documentPath,
                'review_status' => 'pending',
            ]);

            // Log status pertama
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'changed_at' => now(),
            ]);

            // Kirim Notifikasi
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan proposal event berhasil dikirim.',
                'order' => Order::with('eventProposal')->find($order->id),
            ], 201);
        });
    }

    /**
     * Konfirmasi Pesanan oleh Driver/Admin.
     */
    public function postConfirmOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan tidak dalam status Menunggu Konfirmasi.',
            ], 400);
        }

        return DB::transaction(function () use ($order) {
            $order->status = 'confirmed';
            $order->save();

            // Jika pesanan sudah memiliki armada, set armada tersebut menjadi 'on_duty'
            if ($order->ambulance_id) {
                $ambulance = Ambulance::find($order->ambulance_id);
                $ambulance->status = 'on_duty';
                $ambulance->save();
            }

            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'confirmed',
                'changed_at' => now(),
            ]);

            // Kirim Notifikasi
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pesanan berhasil dikonfirmasi.',
                'order' => $order,
            ]);
        });
    }

    /**
     * Memperbarui Status Perjalanan (Live Trip Status).
     */
    public function postUpdateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:on_the_way,arrived,transporting,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($id);

        return DB::transaction(function () use ($order, $request) {
            $oldStatus = $order->status;
            $newStatus = $request->status;

            $order->status = $newStatus;
            $order->save();

            // Catat riwayat perubahan status
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'changed_at' => now(),
            ]);

            // Jika status trip selesai (completed) atau batal (cancelled), kembalikan status ambulans menjadi 'standby'
            if (in_array($newStatus, ['completed', 'cancelled'])) {
                if ($order->ambulance_id) {
                    $ambulance = Ambulance::find($order->ambulance_id);
                    $ambulance->status = 'standby';
                    $ambulance->save();
                }
            }

            // Kirim Notifikasi
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            return response()->json([
                'status' => 'success',
                'message' => "Status perjalanan berhasil diperbarui menjadi {$newStatus}.",
                'order' => $order,
            ]);
        });
    }

    /**
     * Memperbarui lokasi GPS ambulans real-time selama perjalanan (Live Tracking).
     */
    public function postUpdateLocation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'bearing' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($id);

        // Memastikan order dalam status aktif berjalan
        if (!in_array($order->status, ['confirmed', 'on_the_way', 'arrived', 'transporting'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan tidak aktif berjalan. Tidak dapat memperbarui lokasi.',
            ], 400);
        }

        if (!$order->ambulance_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Armada ambulans belum ditentukan untuk pesanan ini.',
            ], 400);
        }

        return DB::transaction(function () use ($order, $request) {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $bearing = $request->bearing;

            $ambulance = Ambulance::findOrFail($order->ambulance_id);

            // Validasi location spoofing
            $isValid = app(\App\Services\ValidateLocationUpdate::class)->isValid(
                $ambulance, $latitude, $longitude, now()
            );

            if (!$isValid) {
                // Catat ke location_anomalies
                \App\Models\LocationAnomaly::create([
                    'ambulance_id' => $ambulance->id,
                    'reported_lat' => $latitude,
                    'reported_lng' => $longitude,
                    'flagged_at' => now(),
                ]);

                return response()->json([
                    'status' => 'flagged',
                    'message' => 'Laporan lokasi ditandai sebagai anomali.',
                ], 202);
            }

            // 1. Simpan koordinat ke riwayat trip_tracking_logs
            TripTrackingLog::create([
                'order_id' => $order->id,
                'coordinates' => DB::raw("Point({$longitude}, {$latitude})"),
                'recorded_at' => now(),
            ]);

            // 2. Perbarui lokasi terkini ambulans
            $ambulance->current_location = DB::raw("Point({$longitude}, {$latitude})");
            $ambulance->last_location_at = now();
            $ambulance->save();

            // Reset status stale signal jika sebelumnya dilaporkan hilang
            if ($order->tracking_lost_notified_at) {
                $order->update(['tracking_lost_notified_at' => null]);
            }

            // 3. Broadcast lokasi terbaru via WebSocket
            event(new AmbulanceLocationUpdated(
                $order->id,
                $order->ambulance_id,
                $latitude,
                $longitude,
                $bearing
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Lokasi ambulans berhasil diperbarui dan disiarkan.',
            ]);
        });
    }

    /**
     * Konfirmasi penerimaan panggilan SOS oleh Driver.
     */
    public function postAcceptOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        return DB::transaction(function () use ($order) {
            // Lock the order row to prevent race conditions
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'awaiting_driver_response') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order sudah tidak tersedia atau sudah dialihkan ke armada lain.',
                ], 409); // 409 Conflict
            }

            // Update order status to confirmed
            $order->status = 'confirmed';
            $order->save();

            // Update ambulance status to on_duty
            if ($order->ambulance_id) {
                $ambulance = Ambulance::find($order->ambulance_id);
                if ($ambulance) {
                    $ambulance->status = 'on_duty';
                    $ambulance->save();
                }
            }

            // Log response time in SosLog
            $sosLog = SosLog::where('order_id', $order->id)->first();
            if ($sosLog) {
                $dispatchedAt = $sosLog->dispatched_at ?? $order->created_at;
                $responseTimeSeconds = (int) abs(now()->diffInSeconds($dispatchedAt));
                $sosLog->update([
                    'response_time_seconds' => $responseTimeSeconds,
                ]);
            }

            // Log status history
            TripStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'confirmed',
                'changed_at' => now(),
            ]);

            // Kirim Notifikasi ke User
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            // Broadcast real-time update using TripStatusUpdated event
            broadcast(new \App\Events\TripStatusUpdated($order));

            return response()->json([
                'status' => 'success',
                'message' => 'Panggilan darurat berhasil Anda terima.',
                'order' => $order,
            ]);
        });
    }
}
