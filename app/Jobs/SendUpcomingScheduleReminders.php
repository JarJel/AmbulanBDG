<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\UpcomingScheduleReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendUpcomingScheduleReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tomorrow = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        $upcomingOrders = Order::whereIn('order_type', ['sewa', 'event'])
            ->where('status', 'confirmed')
            ->whereBetween('scheduled_start_time', [$tomorrow, $tomorrowEnd])
            ->whereNull('reminder_sent_at') // cegah kirim dobel
            ->with(['user', 'ambulance.currentDriver'])
            ->get();

        foreach ($upcomingOrders as $order) {
            $order->user->notify(new UpcomingScheduleReminder($order));

            if ($order->ambulance && $order->ambulance->currentDriver) {
                $order->ambulance->currentDriver->notify(new UpcomingScheduleReminder($order));
            }

            $order->update(['reminder_sent_at' => now()]);
        }
    }
}
