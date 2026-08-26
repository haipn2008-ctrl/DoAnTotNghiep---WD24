<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractRepresentativeTransfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_at' => 'datetime',
        'deposit_amount_snapshot' => 'decimal:2',
        'old_representative_snapshot' => 'array',
        'new_representative_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Phụ lục chuyển giao là dữ liệu bất biến.');
        });
        static::deleting(function (): never {
            throw new LogicException('Không được xóa phụ lục chuyển giao.');
        });
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function oldContractTenant()
    {
        return $this->belongsTo(ContractTenant::class, 'old_contract_tenant_id');
    }

    public function newContractTenant()
    {
        return $this->belongsTo(ContractTenant::class, 'new_contract_tenant_id');
    }

    public function oldTenant()
    {
        return $this->belongsTo(Tenant::class, 'old_tenant_id');
    }

    public function newTenant()
    {
        return $this->belongsTo(Tenant::class, 'new_tenant_id');
    }

    public function oldUser()
    {
        return $this->belongsTo(User::class, 'old_user_id');
    }

    public function newUser()
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
