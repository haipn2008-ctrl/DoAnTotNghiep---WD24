<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'price',
        'area',
        'status'
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function utilityRecords()
    {
        return $this->hasMany(UtilityReading::class);
    }
}
