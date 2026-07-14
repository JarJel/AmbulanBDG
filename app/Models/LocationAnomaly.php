<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ambulance_id', 'reported_lat', 'reported_lng', 'flagged_at'])]
class LocationAnomaly extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_lat' => 'float',
            'reported_lng' => 'float',
            'flagged_at' => 'datetime',
        ];
    }

    /**
     * Get the ambulance associated with this anomaly.
     */
    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }
}
