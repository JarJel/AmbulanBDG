<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['order_id', 'document_path', 'review_status', 'notes'])]
class EventProposal extends Model
{
    use HasFactory;

    /**
     * Get the order that contains this proposal.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
