<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'coordinates', 'recorded_at'])]
#[Hidden(['coordinates'])]
class TripTrackingLog extends Model
{
    use HasFactory;

    // Disable standard timestamps because we have custom recorded_at timestamp
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the order associated with this tracking log.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
