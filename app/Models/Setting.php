<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
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
}
