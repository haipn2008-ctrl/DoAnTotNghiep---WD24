<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'full_name',
        'cccd',
        'phone',
        'email',
        'address'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
