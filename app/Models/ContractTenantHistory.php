<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractTenantHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Lịch sử người thuê là bất biến.');
        });
        static::deleting(function (): never {
            throw new LogicException('Không được xóa lịch sử người thuê.');
        });
    }

    public function contractTenant()
    {
        return $this->belongsTo(ContractTenant::class, 'contract_tenant_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
