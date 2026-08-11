<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UtilityReading extends Model
{
    protected $fillable = [
        'room_id', 'contract_id', 'month', 'year', 'reading_type',
        'record_date',
        'electricity_old', 'electricity_new',
        'electricity_image',
        'water_old', 'water_new',
        'water_image',
        'status',
        'note',
        'lifecycle_event_key',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public function meterImageExists(string $type): bool
    {
        if (! in_array($type, ['electricity', 'water'], true)) {
            return false;
        }

        $path = $this->{$type.'_image'};

        return filled($path) && Storage::disk('local')->exists($path);
    }

    // Lấy thông tin phòng tương ứng
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Hàm phụ trợ tính số lượng tiêu thụ
    public function getElectricityUsageAttribute()
    {
        return $this->electricity_new - $this->electricity_old;
    }

    public function getWaterUsageAttribute()
    {
        return $this->water_new - $this->water_old;
    }
}
