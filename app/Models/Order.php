<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id', 'ambulance_id', 'order_type', 'status', 
    'scheduled_start_time', 'scheduled_end_time', 
    'pickup_location', 'pickup_address', 
    'destination_location', 'destination_address', 
    'fare_amount', 'bpjs_number', 'bpjs_status', 'tracking_lost_notified_at',
    'reminder_sent_at', 'patient_condition',
    'guest_phone', 'guest_session_token', 'guest_token_expires_at'
])]
#[Hidden(['pickup_location', 'destination_location'])]
class Order extends Model
{
    use HasFactory;

    protected $appends = ['pickup_latitude', 'pickup_longitude', 'destination_latitude', 'destination_longitude'];

    public function getPickupLatitudeAttribute()
    {
        return $this->attributes['pickup_latitude'] ?? null;
    }

    public function getPickupLongitudeAttribute()
    {
        return $this->attributes['pickup_longitude'] ?? null;
    }

    public function getDestinationLatitudeAttribute()
    {
        return $this->attributes['destination_latitude'] ?? null;
    }

    public function getDestinationLongitudeAttribute()
    {
        return $this->attributes['destination_longitude'] ?? null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_start_time' => 'datetime',
            'scheduled_end_time' => 'datetime',
            'fare_amount' => 'decimal:2',
            'tracking_lost_notified_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the user (customer) who placed the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ambulance assigned to the order.
     */
    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }

    /**
     * Get the event proposal associated with the order.
     */
    public function eventProposal()
    {
        return $this->hasOne(EventProposal::class);
    }

    /**
     * Get the status history logs for the order.
     */
    public function statusHistory()
    {
        return $this->hasMany(TripStatusHistory::class);
    }

    /**
     * Get the dynamic coordinate logs for the order's trip.
     */
    public function trackingLogs()
    {
        return $this->hasMany(TripTrackingLog::class);
    }

    /**
     * Get the payment details for the order.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the rating given for the order.
     */
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    /**
     * Get the SOS logs (SLA details) for the order.
     */
    public function sosLog()
    {
        return $this->hasOne(SosLog::class);
    }

    /**
     * Get the patient details associated with the order.
     */
    public function patientDetail()
    {
        return $this->hasOne(PatientDetail::class);
    }
}
