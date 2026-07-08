<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const TYPE_ROOM = 'room';

    const TYPE_ELECTRIC = 'electric';

    const TYPE_WATER = 'water';

    const TYPE_INTERNET = 'internet';

    const TYPE_SERVICE = 'service';

    const TYPE_OTHER = 'other';

    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [
        'invoice_id',
        'item_name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Thuộc hóa đơn.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Thuộc kỳ ghi điện nước.
     */
    public function utilityReading()
    {
        return $this->belongsTo(UtilityReading::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isRoomFee()
    {
        return $this->type === self::TYPE_ROOM;
    }

    public function isElectricFee()
    {
        return $this->type === self::TYPE_ELECTRIC;
    }

    public function isWaterFee()
    {
        return $this->type === self::TYPE_WATER;
    }

    public function isInternetFee()
    {
        return $this->type === self::TYPE_INTERNET;
    }

    public function isServiceFee()
    {
        return $this->type === self::TYPE_SERVICE;
    }

    public function isOtherFee()
    {
        return $this->type === self::TYPE_OTHER;
    }

    /*
    |--------------------------------------------------------------------------
    | Danh sách loại chi phí
    |--------------------------------------------------------------------------
    */

    public static function getTypes(): array
    {
        return [
            self::TYPE_ROOM,
            self::TYPE_ELECTRIC,
            self::TYPE_WATER,
            self::TYPE_INTERNET,
            self::TYPE_SERVICE,
            self::TYPE_OTHER,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    /**
     * Thành tiền được format.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }
}
