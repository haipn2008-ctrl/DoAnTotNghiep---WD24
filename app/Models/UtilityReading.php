<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityReading extends Model
{
    protected $fillable = [
        'room_id', 'month', 'year', 
        'electricity_old', 'electricity_new', 
        'water_old', 'water_new', 'status'
    ];

    // Lấy thông tin phòng tương ứng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
    
    // Hàm phụ trợ tính số lượng tiêu thụ
    public function getElectricityUsageAttribute() {
        return $this->electricity_new - $this->electricity_old;
    }

    public function getWaterUsageAttribute() {
        return $this->water_new - $this->water_old;
    }
}
