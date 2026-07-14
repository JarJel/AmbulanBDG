<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    /**
     * Kirim ulasan dan penilaian (bintang) untuk pesanan yang telah selesai.
     */
    public function postRateOrder(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating_value' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($id);

        // 1. Validasi status order wajib 'completed'
        if ($order->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya pesanan yang telah selesai yang dapat diberikan ulasan.',
            ], 400);
        }

        // 2. Validasi kepemilikan order (hanya pemesan asli yang bisa mengulas)
        $user = $request->user();
        if ($user && (int) $order->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk memberikan ulasan pada pesanan ini.',
            ], 403);
        }

        // 3. Validasi anti-ulasan ganda
        $alreadyRated = Rating::where('order_id', $order->id)->exists();
        if ($alreadyRated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesanan ini sudah pernah diberikan ulasan.',
            ], 400);
        }

        // Simpan Rating
        $rating = Rating::create([
            'order_id' => $order->id,
            'rating_value' => $request->rating_value,
            'review_text' => $request->review_text,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ulasan berhasil dikirim.',
            'rating' => $rating,
        ], 201);
    }
}
