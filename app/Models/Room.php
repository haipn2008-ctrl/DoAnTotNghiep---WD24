<?php

namespace App\Models;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'floor',
        'price',
        'area',
        'max_people',
        'current_people',
        'thumbnail',
        'description',
        'status',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function utilityRecords()
    {
        return $this->hasMany(UtilityReading::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class,
            'amenity_room'

        );
    }
}
