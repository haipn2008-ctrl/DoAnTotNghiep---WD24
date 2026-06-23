<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [

        'full_name',
        'date_of_birth',

        'cccd',
        'cccd_issue_date',
        'cccd_issue_place',

        'phone',
        'email',

        'address',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
