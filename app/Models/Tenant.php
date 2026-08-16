<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'user_id',

        'full_name',
        'date_of_birth',
        'gender',

        'cccd',
        'cccd_issue_date',
        'cccd_issue_place',

        'phone',
        'email',

        'address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'cccd_issue_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function representativeContracts()
    {
        return $this->hasMany(
            Contract::class,
            'representative_tenant_id'
        );
    }

    /**
     * Giấy tờ nhận diện / CCCD
     */
    public function document(): HasOne
    {
        return $this->hasOne(
            TenantDocument::class
        );
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function temporaryResidences()
    {
        return $this->hasMany(TemporaryResidence::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function activeContract()
    {
        return $this->contracts()
            ->where('status', 'active')
            ->first();
    }

    public function isRenting()
    {
        return $this->contracts()
            ->where('status', 'active')
            ->exists();
    }
}
