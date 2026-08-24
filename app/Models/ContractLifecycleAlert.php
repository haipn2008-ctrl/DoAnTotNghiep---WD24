<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractLifecycleAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'signature_overdue' => 'Quá hạn ký',
            'deposit_overdue' => 'Quá hạn cọc',
            'move_in_overdue' => 'Quá hạn nhận phòng',
            'contract_expired' => 'Hợp đồng hết hạn',
            'deposit_exception' => 'Ngoại lệ tiền cọc',
            'cancelled_deposit_resolution' => 'Cọc hợp đồng đã hủy',
            'vehicle_review' => 'Phương tiện chờ duyệt',
            'vehicle_removed' => 'Phương tiện đã gỡ',
            default => 'Cần xử lý',
        };
    }
}
