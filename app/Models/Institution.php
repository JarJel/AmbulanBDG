<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'verified_status', 'license_number', 'address', 'phone'])]
class Institution extends Model
{
    use HasFactory;

    /**
     * Get the users/staff associated with the institution.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the admins associated with the institution.
     */
    public function admins()
    {
        return $this->hasMany(User::class)->where('role', 'institution_admin');
    }

    /**
     * Get the ambulances owned by the institution.
     */
    public function ambulances()
    {
        return $this->hasMany(Ambulance::class);
    }
}
