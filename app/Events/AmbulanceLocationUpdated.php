<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AmbulanceLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $ambulanceId;
    public $latitude;
    public $longitude;
    public $bearing;
    public $recordedAt;

    /**
     * Create a new event instance.
     */
    public function __construct($orderId, $ambulanceId, $latitude, $longitude, $bearing = null, $recordedAt = null)
    {
        $this->orderId = $orderId;
        $this->ambulanceId = $ambulanceId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->bearing = $bearing;
        $this->recordedAt = $recordedAt ?? now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('orders.' . $this->orderId),
        ];

        $order = \App\Models\Order::find($this->orderId);
        if ($order && $order->guest_session_token) {
            $channels[] = new Channel('orders.guest.' . $this->orderId . '.' . $order->guest_session_token);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'ambulance_id' => $this->ambulanceId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'bearing' => $this->bearing,
            'recorded_at' => $this->recordedAt,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }
}
