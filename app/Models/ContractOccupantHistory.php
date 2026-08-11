<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractOccupantHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Lịch sử người ở là bất biến.');
        });
        static::deleting(function (): never {
            throw new LogicException('Không được xóa lịch sử người ở.');
        });
    }

    public function occupant()
    {
        return $this->belongsTo(ContractOccupant::class, 'contract_occupant_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
