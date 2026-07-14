<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'rating_value', 'review_text'])]
class Rating extends Model
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
            'rating_value' => 'integer',
        ];
    }

    /**
     * Get the order associated with the rating.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
