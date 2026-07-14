<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'ambulance_id', 'dispatched_at', 'response_time_seconds', 'escalated'])]
class SosLog extends Model
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
            'response_time_seconds' => 'integer',
            'escalated' => 'boolean',
            'dispatched_at' => 'datetime',
        ];
    }

    /**
     * Get the order associated with this SOS log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the ambulance associated with this SOS log.
     */
    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }
}
