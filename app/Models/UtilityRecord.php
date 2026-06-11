<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityRecord extends Model
{
    protected $fillable = [
        'room_id',
        'electric_old',
        'electric_new',
        'water_old',
        'water_new',
        'record_month'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
