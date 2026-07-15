<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVerification extends Model
{
    protected $fillable = [
        'verifiable_id',
        'verifiable_type',
        'document_type',
        'file_path',
        'expiry_date',
        'status',
        'rejection_notes',
        'verified_by_user_id',
        'verified_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function verifiable()
    {
        return $this->morphTo();
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
