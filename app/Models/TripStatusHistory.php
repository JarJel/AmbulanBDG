<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'status', 'changed_at'])]
class TripStatusHistory extends Model
{
    use HasFactory;

    // Disable standard timestamps because we have custom changed_at timestamp
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the order associated with this status history.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
