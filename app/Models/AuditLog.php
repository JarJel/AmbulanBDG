<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'action', 'description', 'ip_address', 'created_at'])]
class AuditLog extends Model
{
    use HasFactory;

    // Only created_at is used
    const UPDATED_AT = null;

    /**
     * Get the user who performed the administrative action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
