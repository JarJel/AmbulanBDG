<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Order;
use Symfony\Component\HttpFoundation\Response;

class VerifyGuestTrackingToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $orderId = $request->route('id') ?? $request->input('order_id');
        $token = $request->route('token') ?? $request->input('guest_session_token') ?? $request->header('X-Guest-Token');

        if (!$orderId || !$token) {
            abort(400, 'Parameter order_id atau token tidak valid.');
        }

        // Anti Brute-Force Rate Limiting per IP
        $ip = $request->ip();
        $rateLimitKey = 'track-attempt:' . $ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            abort(429, 'Terlalu banyak percobaan pencarian token salah. Silakan coba 5 menit lagi.');
        }

        $order = Order::where('id', $orderId)
            ->where('guest_session_token', $token)
            ->first();

        if (!$order) {
            RateLimiter::hit($rateLimitKey, 300); // lock 5 menit
            abort(403, 'Akses ditolak. Token pelacakan tamu tidak valid.');
        }

        // Cek kedaluwarsa token
        if ($order->guest_token_expires_at && now()->greaterThan($order->guest_token_expires_at)) {
            abort(403, 'Akses ditolak. Token pelacakan tamu sudah kedaluwarsa.');
        }

        // Share the order inside request for convenience
        $request->attributes->set('guest_order', $order);

        return $next($request);
    }
}
