<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [

        'room_code',

        'floor',

        'price',

        'area',

        'max_people',

        // Tạm thời giữ, sau sẽ thay bằng Occupants
        'current_people',

        'thumbnail',

        'description',

        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Tất cả hợp đồng
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // Hợp đồng đang hoạt động
    public function activeContract()
    {
        return $this->hasOne(Contract::class)
            ->where('status', 'active');
    }

    // Chỉ số điện nước
    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class);
    }

    // Tiện ích
    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class,
            'amenity_room'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAvailable()
    {
        return $this->status === 'available';
    }

    public function isOccupied()
    {
        return $this->status === 'occupied';
    }

    public function isMaintenance()
    {
        return $this->status === 'maintenance';
    }
}
