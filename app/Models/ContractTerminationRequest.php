<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTerminationRequest extends Model
{
    // ==============================
    // STATUS
    // ==============================

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const TYPE_EARLY_TERMINATION = 'early_termination';

    public const TYPE_END_OF_TERM = 'end_of_term';

    public const TYPE_OVERDUE_DEPARTURE = 'overdue_departure';

    protected $fillable = [
        'contract_id',
        'tenant_id',
        'requested_end_date',
        'reason',
        'request_type',
        'status',
        'admin_note',
        'approved_end_date',
        'scheduled_checkout_at',
        'processed_by',
        'processed_at',
        'fulfilled_at',
    ];

    protected $casts = [
        'requested_end_date' => 'date',
        'approved_end_date' => 'date',
        'scheduled_checkout_at' => 'datetime',
        'processed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    /**
     * Hợp đồng
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Khách thuê
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->request_type) {
            self::TYPE_END_OF_TERM => 'Rời đi đúng hạn',
            self::TYPE_OVERDUE_DEPARTURE => 'Bàn giao sau khi quá hạn',
            default => 'Chấm dứt trước hạn',
        };
    }
}
