<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Contract;
use App\Models\Room;
use App\Models\User;
use App\Models\Invoice;

class UtilityReading extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT = 'draft';

    const STATUS_CONFIRMED = 'confirmed';

    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [

        'contract_id',
        'room_id',

        'month',
        'year',
        'record_date',

        'electric_old',
        'electric_new',
        'electric_unit_price',
        'electricity_image',

        'water_old',
        'water_new',
        'water_unit_price',
        'water_image',

        'status',
        'note',

        'recorded_by',
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [

        'record_date' => 'date',

        'electric_unit_price' => 'decimal:2',

        'water_unit_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Thuộc hợp đồng.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Thuộc phòng.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Người nhập chỉ số.
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Một lần ghi chỉ số sinh một hóa đơn.
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Số điện tiêu thụ.
     */
    public function getElectricUsageAttribute()
    {
        return max(
            0,
            $this->electric_new - $this->electric_old
        );
    }

    /**
     * Tiền điện.
     */
    public function getElectricAmountAttribute()
    {
        return $this->electric_usage * $this->electric_unit_price;
    }

    /**
     * Số nước tiêu thụ.
     */
    public function getWaterUsageAttribute()
    {
        return max(
            0,
            $this->water_new - $this->water_old
        );
    }

    /**
     * Tiền nước.
     */
    public function getWaterAmountAttribute()
    {
        return $this->water_usage * $this->water_unit_price;
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helper
    |--------------------------------------------------------------------------
    */

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
