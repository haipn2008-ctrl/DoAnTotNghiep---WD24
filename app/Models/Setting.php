<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [

        'electric_price',

        'water_price',

        'internet_fee',

        'service_fee',

        'parking_fee',

        'invoice_day',

        'payment_due_days',

        'is_active',
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [

        'electric_price' => 'decimal:2',

        'water_price' => 'decimal:2',

        'internet_fee' => 'decimal:2',

        'service_fee' => 'decimal:2',

        'parking_fee' => 'decimal:2',

        'invoice_day' => 'integer',

        'payment_due_days' => 'integer',

        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    /**
     * Lấy cấu hình hiện đang sử dụng
     */
    public static function current()
    {
        return self::where('is_active', true)->first();
    }
}
