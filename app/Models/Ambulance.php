<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'institution_id', 'plate_number', 'category', 'status', 'current_driver_id', 'current_nurse_id', 
    'current_location', 'last_location_at', 'brand', 'year', 'capacity', 'is_draft', 'verification_status',
    'base_fare', 'price_per_km', 'is_free_service_eligible'
])]
#[Hidden(['current_location'])]
class Ambulance extends Model
{
    use HasFactory;

    protected $appends = ['average_rating', 'latitude', 'longitude'];

    public function getLatitudeAttribute()
    {
        return $this->attributes['latitude'] ?? null;
    }

    public function getLongitudeAttribute()
    {
        return $this->attributes['longitude'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'last_location_at' => 'datetime',
        ];
    }

    /**
     * Get the institution that owns the ambulance.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the current driver of the ambulance.
     */
    public function currentDriver()
    {
        return $this->belongsTo(User::class, 'current_driver_id');
    }

    /**
     * Get the current nurse of the ambulance.
     */
    public function currentNurse()
    {
        return $this->belongsTo(User::class, 'current_nurse_id');
    }

    /**
     * Get the documents associated with the vehicle.
     */
    public function vehicleDocuments()
    {
        return $this->hasMany(VehicleDocument::class);
    }

    /**
     * Get the orders served by this ambulance.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all ratings for this ambulance through orders.
     */
    public function ratings()
    {
        return $this->hasManyThrough(Rating::class, Order::class);
    }

    /**
     * Get dynamic average rating.
     */
    public function getAverageRatingAttribute()
    {
        return round($this->ratings()->avg('rating_value') ?? 0.0, 1);
    }

    /**
     * Get the location anomalies for the ambulance.
     */
    public function locationAnomalies()
    {
        return $this->hasMany(LocationAnomaly::class);
    }

    /**
     * Get polymorphic documents.
     */
    public function documents()
    {
        return $this->morphMany(DocumentVerification::class, 'verifiable');
    }

    /**
     * Get tariff rate change history.
     */
    public function rateHistories()
    {
        return $this->hasMany(RateHistory::class);
    }

    /**
     * Get shift vehicle checklists.
     */
    public function checklists()
    {
        return $this->hasMany(VehicleChecklist::class);
    }
}
