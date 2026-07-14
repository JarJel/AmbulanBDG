<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Ambulance;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    /**
     * Mengikat driver/perawat ke unit ambulans tertentu.
     */
    public function postAssignAmbulance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ambulance_id' => 'required|exists:ambulances,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $ambulance = Ambulance::findOrFail($request->ambulance_id);

        return DB::transaction(function () use ($user, $ambulance) {
            if ($user->role === 'driver') {
                $ambulance->current_driver_id = $user->id;
            } elseif ($user->role === 'nurse') {
                $ambulance->current_nurse_id = $user->id;
            }

            // Set status menjadi standby (online) jika kru ditugaskan
            $ambulance->status = 'standby';
            $ambulance->save();

            // Catat Log Audit
            AuditLogger::log(
                $user->id,
                'assign_ambulance_staff',
                "Staf {$user->name} (Role: {$user->role}) mengikat diri ke Ambulans {$ambulance->plate_number}."
            );

            return response()->json([
                'status' => 'success',
                'message' => "Anda berhasil terikat ke armada {$ambulance->plate_number}.",
                'ambulance' => $ambulance,
            ]);
        });
    }

    /**
     * Mengubah status online/offline armada yang sedang dikendarai oleh staf tersebut.
     */
    public function postToggleStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:standby,offline',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        // Cari ambulans yang dikendarai oleh staf ini
        $ambulance = Ambulance::where('current_driver_id', $user->id)
            ->orWhere('current_nurse_id', $user->id)
            ->first();

        if (!$ambulance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda harus terikat ke armada ambulans terlebih dahulu.',
            ], 400);
        }

        return DB::transaction(function () use ($ambulance, $request, $user) {
            $oldStatus = $ambulance->status;
            $ambulance->status = $request->status;
            $ambulance->save();

            // Catat Log Audit
            AuditLogger::log(
                $user->id,
                'toggle_ambulance_status',
                "Staf {$user->name} mengubah status ambulans {$ambulance->plate_number} dari {$oldStatus} menjadi {$request->status}."
            );

            return response()->json([
                'status' => 'success',
                'message' => "Status armada berhasil diperbarui menjadi {$request->status}.",
                'ambulance' => $ambulance,
            ]);
        });
    }

    /**
     * Mendapatkan tugas perjalanan (trip) aktif saat ini untuk unit ambulans kru.
     */
    public function getActiveTrips(Request $request)
    {
        $user = $request->user();

        // Cari ambulans yang dikendarai oleh staf ini
        $ambulance = Ambulance::where('current_driver_id', $user->id)
            ->orWhere('current_nurse_id', $user->id)
            ->first();

        if (!$ambulance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda harus terikat ke armada ambulans terlebih dahulu.',
            ], 400);
        }

        // Cari order aktif untuk armada ini
        $order = Order::where('ambulance_id', $ambulance->id)
            ->whereIn('status', ['awaiting_driver_response', 'confirmed', 'on_the_way', 'arrived', 'transporting'])
            ->first();

        return response()->json([
            'status' => 'success',
            'order' => $order,
        ]);
    }
}
