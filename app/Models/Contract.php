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
        'signed_at',
        'monthly_rent',
        'deposit_amount',
        'number_of_people',
        'terminated_at',
        'actual_end_date',
        'termination_reason',
        'termination_note',
        'contract_file',
        'status',
        'note',
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
