<?php

namespace App\Models;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'floor',
        'room_type',
        'price',
        'area',
        'max_people',
        'description',
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

    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class
        );
    }
    // Lấy phòng
    public function getRoomTypeTextAttribute()
    {
        return match ($this->room_type) {
            'standard' => 'Phòng thường',
            'vip' => 'Phòng VIP',
            default => 'Không xác định'
        };
    }
}
