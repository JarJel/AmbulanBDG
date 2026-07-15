<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleChecklist extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ambulance_id',
        'driver_user_id',
        'fuel_status',
        'oxygen_status',
        'medical_supplies_status',
        'notes',
    ];

    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}
