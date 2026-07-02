<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityReading extends Model
{
    protected $fillable = [

        'room_id',

        'month',
        'year',

        'record_date',

        'electricity_old',
        'electricity_new',

        'water_old',
        'water_new',

        'electricity_image',
        'water_image',

        'status',

        'note',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Phòng được ghi chỉ số
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Một lần ghi chỉ số có thể sinh nhiều dòng chi tiết hóa đơn
    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Số điện tiêu thụ
    public function getElectricityUsageAttribute()
    {
        return max(0, $this->electricity_new - $this->electricity_old);
    }

    // Số nước tiêu thụ
    public function getWaterUsageAttribute()
    {
        return max(0, $this->water_new - $this->water_old);
    }
}
