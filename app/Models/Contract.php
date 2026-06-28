<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'contract_code',
        'room_id',
        'tenant_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'deposit_amount',
        'status',
        'extend_reason',
        'extend_note',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
