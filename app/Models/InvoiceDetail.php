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
     * Các trường được phép gán dữ liệu hàng loạt.
     */
    protected $fillable = [

        'invoice_id',

        'type',

        'name',

        'quantity',

        'unit',

        'unit_price',

        'amount',

        'old_index',

        'new_index',

        'note',

        'sort_order',
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [

        'quantity'   => 'decimal:2',

        'unit_price' => 'decimal:2',

        'amount'     => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Thuộc về một hóa đơn.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
    | Danh sách các loại chi tiết hóa đơn
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
}
