<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Room Status
    |--------------------------------------------------------------------------
    */

    const STATUS_AVAILABLE   = 'available';

    const STATUS_OCCUPIED    = 'occupied';

    const STATUS_MAINTENANCE = 'maintenance';

    /**
     * Các trường được phép gán dữ liệu.
     */
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

    /**
     * Tất cả hợp đồng
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Hợp đồng đang hoạt động
     */
    public function currentContract()
    {
        return $this->hasOne(Contract::class)
            ->whereIn('status',[
                Contract::STATUS_DRAFT,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_SIGNED,
                Contract::STATUS_DEPOSIT_PAID,
                Contract::STATUS_ACTIVE
            ]);
    }

    /**
     * Chỉ số điện nước
     */
    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class);
    }

    /**
     * Tiện ích
     */
    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class,
            'amenity_room'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeMaintenance($query)
    {
        return $query->where('status', self::STATUS_MAINTENANCE);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAvailable()
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied()
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isMaintenance()
    {
        return $this->status === self::STATUS_MAINTENANCE;
    }
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'Còn trống',
            self::STATUS_OCCUPIED => 'Đang thuê',
            self::STATUS_MAINTENANCE => 'Đang bảo trì',
            default => 'Không xác định',
        };
    }
    
}
