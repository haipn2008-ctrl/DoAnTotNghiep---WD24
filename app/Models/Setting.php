<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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
        $query = self::query();

        if (Schema::hasColumn((new self())->getTable(), 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->first();
    }

    public static function currentOrCreate(array $defaults = []): self
    {
        $setting = self::current();

        if ($setting) {
            return $setting;
        }

        $payload = array_merge([
            'electric_price' => 0,
            'water_price' => 0,
            'internet_fee' => 0,
            'service_fee' => 0,
            'parking_fee' => 0,
            'invoice_day' => 5,
            'payment_due_days' => 10,
        ], $defaults);

        if (Schema::hasColumn((new self())->getTable(), 'is_active')) {
            $payload['is_active'] = true;
        }

        return self::create($payload);
    }
}
