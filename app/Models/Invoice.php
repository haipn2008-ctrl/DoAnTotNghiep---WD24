<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'contract_id',
        'room_id',
        'utility_reading_id',
        'month',
        'year',
        'invoice_date',
        'due_date',
        'room_fee',
        'electricity_fee',
        'water_fee',
        'internet_fee',
        'service_fee',
        'total_amount',
        'status'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function utilityReading()
    {
        return $this->belongsTo(UtilityReading::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class)->orderBy('sort_order');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
