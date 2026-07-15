<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ambulance_id',
        'old_base_fare',
        'new_base_fare',
        'old_price_per_km',
        'new_price_per_km',
        'changed_by_user_id',
    ];

    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
