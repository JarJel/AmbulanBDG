<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ambulance_id', 'doc_type', 'document_path', 'verification_status', 'expiry_date', 'alert_sent_at'])]
class VehicleDocument extends Model
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
            'expiry_date' => 'date',
            'alert_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the ambulance that owns the document.
     */
    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class);
    }
}
