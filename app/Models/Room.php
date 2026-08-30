<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa phòng. Hãy ngừng khai thác để có thể khôi phục khi cần.');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Room Status
    |--------------------------------------------------------------------------
    */

    const STATUS_AVAILABLE = 'available';

    const STATUS_OCCUPIED = 'occupied';

    const STATUS_MAINTENANCE = 'maintenance';

    const STATUS_RETIRED = 'retired';

    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [

        'room_code',

        'floor',

        'price',

        'area',

        'max_people',

        // Tạm thời giữ, sau sẽ thay bằng Members
        'current_people',

        'thumbnail',

        'description',

        'status',

        'retired_at',

        'retired_by',

        'retirement_reason',

        'restored_at',

        'restored_by',

        'restoration_reason',
    ];

    protected $casts = [
        'retired_at' => 'datetime',
        'restored_at' => 'datetime',
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
        return $this->reservingContract();
    }

    /**
     * Hợp đồng đang giữ chỗ hoặc đang sử dụng phòng.
     */
    public function reservingContract()
    {
        return $this->hasOne(Contract::class)
            ->whereIn('status', Contract::RESERVING_STATUSES);
    }

    /**
     * Chỉ hợp đồng đang hoạt động
     */
    public function activeContract()
    {
        return $this->hasOne(Contract::class)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES);
    }

    /**
     * Chỉ số điện nước
     */
    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class);
    }

    public function outgoingTransfers()
    {
        return $this->hasMany(RoomTransfer::class, 'old_room_id');
    }

    public function incomingTransfers()
    {
        return $this->hasMany(RoomTransfer::class, 'new_room_id');
    }

    /**
     * Tài sản bàn giao đang áp dụng cho phòng.
     */
    public function amenities()
    {
        return $this->belongsToMany(
            Amenity::class,
            'amenity_room'
        )->where('amenities.is_active', true)
            ->withPivot(['quantity', 'condition', 'note'])
            ->withTimestamps();
    }

    /**
     * Nhật ký ảnh hiện trạng của phòng. Ảnh chỉ được thêm mới để giữ bằng chứng.
     */
    public function images()
    {
        return $this->hasMany(RoomImage::class)->latest('taken_at')->latest('id');
    }

    public function retiredBy()
    {
        return $this->belongsTo(User::class, 'retired_by');
    }

    public function restoredBy()
    {
        return $this->belongsTo(User::class, 'restored_by');
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

    public function scopeRetired($query)
    {
        return $query->where('status', self::STATUS_RETIRED);
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
            self::STATUS_RETIRED => 'Ngừng khai thác',
            default => 'Không xác định',
        };
    }
}
