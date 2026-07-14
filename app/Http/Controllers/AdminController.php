<?php

namespace App\Http\Controllers;

use App\Helpers\AuditLogger;
use App\Models\Ambulance;
use App\Models\EventProposal;
use App\Models\Order;
use App\Models\StaffProfile;
use App\Models\TripStatusHistory;
use App\Models\User;
use App\Models\VehicleDocument;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Verifikasi dokumen kendaraan STNK/KIR.
     */
    public function postVerifyDocument(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'verification_status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $doc = VehicleDocument::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('verify', $doc);

        return DB::transaction(function () use ($doc, $request) {
            $doc->verification_status = $request->verification_status;
            $doc->save();

            // Catat ke Audit Log
            $adminId = $request->user()?->id;
            AuditLogger::log(
                $adminId,
                'verify_vehicle_document',
                "Dokumen {$doc->doc_type} (ID: {$doc->id}) untuk Ambulans ID: {$doc->ambulance_id} di-{$request->verification_status}."
            );

            // Jika disetujui, cek apakah kita bisa memulihkan status ambulans kembali ke standby
            if ($request->verification_status === 'approved') {
                $ambulance = Ambulance::find($doc->ambulance_id);
                if ($ambulance && $ambulance->status === 'suspended') {
                    // Cek apakah ada dokumen lain yang masih kedaluwarsa atau rejected
                    $hasIssues = VehicleDocument::where('ambulance_id', $doc->ambulance_id)
                        ->where(function ($query) {
                            $query->where('verification_status', 'rejected')
                                  ->orWhere('expiry_date', '<', now()->toDateString());
                        })
                        ->exists();

                    if (!$hasIssues) {
                        $ambulance->status = 'standby';
                        $ambulance->save();

                        AuditLogger::log(
                            null, // Sistem
                            'restore_ambulance_status',
                            "Ambulans {$ambulance->plate_number} dipulihkan menjadi standby setelah dokumen STNK/KIR disetujui."
                        );
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Verifikasi dokumen berhasil diperbarui menjadi {$request->verification_status}.",
                'document' => $doc,
            ]);
        });
    }

    /**
     * Verifikasi lisensi driver/perawat SIM/STR.
     */
    public function postVerifyStaff(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'verification_status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $staff = StaffProfile::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('verify', $staff);

        return DB::transaction(function () use ($staff, $request) {
            $staff->verification_status = $request->verification_status;
            $staff->save();

            // Catat ke Audit Log
            $adminId = $request->user()?->id;
            AuditLogger::log(
                $adminId,
                'verify_staff_profile',
                "Profil lisensi staf {$staff->staff_type} (User ID: {$staff->user_id}) di-{$request->verification_status}."
            );

            // Jika disetujui, cek apakah ambulans yang bersangkutan dapat dipulihkan
            if ($request->verification_status === 'approved') {
                $ambulances = Ambulance::where('current_driver_id', $staff->user_id)
                    ->orWhere('current_nurse_id', $staff->user_id)
                    ->get();

                foreach ($ambulances as $ambulance) {
                    if ($ambulance->status === 'suspended') {
                        // Cek apakah ada masalah dokumen kendaraan
                        $hasDocIssues = VehicleDocument::where('ambulance_id', $ambulance->id)
                            ->where(function ($query) {
                                $query->where('verification_status', 'rejected')
                                      ->orWhere('expiry_date', '<', now()->toDateString());
                            })
                            ->exists();

                        if (!$hasDocIssues) {
                            $ambulance->status = 'standby';
                            $ambulance->save();

                            AuditLogger::log(
                                null, // Sistem
                                'restore_ambulance_status',
                                "Ambulans {$ambulance->plate_number} dipulihkan menjadi standby setelah profil staf disetujui."
                            );
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => "Verifikasi staf berhasil diperbarui menjadi {$request->verification_status}.",
                'staff_profile' => $staff,
            ]);
        });
    }

    /**
     * Manajemen armada ambulans dan konfigurasi tarif.
     */
    public function postManageAmbulance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:offline,standby,on_duty,suspended',
            'category' => 'nullable|string',
            'base_fare' => 'nullable|numeric|min:0',
            'price_per_km' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ambulance = Ambulance::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('update', $ambulance);

        return DB::transaction(function () use ($ambulance, $request) {
            $changes = [];

            if ($request->has('status')) {
                $changes[] = "status: {$ambulance->status} -> {$request->status}";
                $ambulance->status = $request->status;
            }

            if ($request->has('category')) {
                $changes[] = "kategori: {$ambulance->category} -> {$request->category}";
                $ambulance->category = $request->category;
            }

            if ($request->has('base_fare')) {
                $changes[] = "tarif dasar: {$ambulance->base_fare} -> {$request->base_fare}";
                $ambulance->base_fare = $request->base_fare;
            }

            if ($request->has('price_per_km')) {
                $changes[] = "tarif per KM: {$ambulance->price_per_km} -> {$request->price_per_km}";
                $ambulance->price_per_km = $request->price_per_km;
            }

            $ambulance->save();

            // Catat ke Audit Log jika ada perubahan
            if (!empty($changes)) {
                $adminId = $request->user()?->id;
                AuditLogger::log(
                    $adminId,
                    'manage_ambulance',
                    "Armada {$ambulance->plate_number} (ID: {$ambulance->id}) diubah: " . implode(', ', $changes)
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Manajemen data dan tarif ambulans berhasil disimpan.',
                'ambulance' => $ambulance,
            ]);
        });
    }

    /**
     * Verifikasi proposal event dan penugasan armada ambulans.
     */
    public function postVerifyEventProposal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'review_status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
            'ambulance_id' => 'required_if:review_status,approved|exists:ambulances,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $proposal = EventProposal::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('verify', $proposal);

        if ($request->review_status === 'approved') {
            $ambulance = Ambulance::findOrFail($request->ambulance_id);
            \Illuminate\Support\Facades\Gate::authorize('update', $ambulance);
        }

        return DB::transaction(function () use ($proposal, $request) {
            $proposal->review_status = $request->review_status;
            $proposal->notes = $request->rejection_reason;
            $proposal->save();

            $order = Order::findOrFail($proposal->order_id);

            if ($request->review_status === 'approved') {
                // Set armada ambulans dan set status order menjadi confirmed
                $order->ambulance_id = $request->ambulance_id;
                $order->status = 'confirmed';
                $order->save();

                // Ubah status ambulans menjadi 'on_duty' karena siaga event
                $ambulance = Ambulance::find($request->ambulance_id);
                if ($ambulance) {
                    $ambulance->status = 'on_duty';
                    $ambulance->save();
                }

                // Buat log status
                TripStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'confirmed',
                    'changed_at' => now(),
                ]);
            } else {
                // Set status order menjadi cancelled
                $order->status = 'cancelled';
                $order->save();

                TripStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'cancelled',
                    'changed_at' => now(),
                ]);
            }

            // Kirim Notifikasi ke Pelanggan
            $user = User::find($order->user_id);
            if ($user) {
                $user->notify(new OrderStatusUpdated($order));
            }

            // Catat ke Audit Log
            $adminId = $request->user()?->id;
            AuditLogger::log(
                $adminId,
                'verify_event_proposal',
                "Proposal Event ID: {$proposal->id} (Order ID: {$order->id}) di-{$request->review_status}."
            );

            return response()->json([
                'status' => 'success',
                'message' => "Proposal event berhasil diperbarui menjadi {$request->review_status}.",
                'proposal' => $proposal,
                'order' => Order::with('ambulance')->find($order->id),
            ]);
        });
    }
}
