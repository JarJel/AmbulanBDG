<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action_type',
        'target_id',
        'target_type',
        'notes',
        'user_id',
    ];

    public function target()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
