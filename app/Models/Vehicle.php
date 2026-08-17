<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'tenant_id',
        'vehicle_type',
        'vehicle_name',
        'license_plate',
        'color',
        'note',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Xe thuộc về khách thuê
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
