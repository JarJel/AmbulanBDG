<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'action', 'description', 'ip_address', 'created_at', 'causer_id', 'causer_type', 'subject_id', 'subject_type', 'properties'])]
class AuditLog extends Model
{
    use HasFactory;

    // Only created_at is used
    const UPDATED_AT = null;

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the user who performed the administrative action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the polymorphic causer model.
     */
    public function causer()
    {
        return $this->morphTo();
    }

    /**
     * Get the polymorphic subject model.
     */
    public function subject()
    {
        return $this->morphTo();
    }
}
