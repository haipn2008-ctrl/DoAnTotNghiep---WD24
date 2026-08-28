<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryResidence extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa hồ sơ tạm trú. Hãy hủy hồ sơ để giữ lịch sử.');
        });
    }

    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'start_date',
        'end_date',
        'status',
        'note',
        'signature',
        'signed_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
