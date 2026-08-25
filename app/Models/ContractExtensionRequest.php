<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractExtensionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'current_end_date',
        'requested_end_date',
        'approved_end_date',
        'proposed_monthly_rent',
        'proposed_deposit_amount',
        'reason',
        'status',
        'admin_note',
        'terms_snapshot',
        'financial_override_reason',
        'processed_by',
        'processed_at',
        'terms_offered_at',
        'tenant_confirmed_at',
        'tenant_declined_at',
        'tenant_decline_reason',
    ];

    protected $casts = [
        'current_end_date'   => 'date',
        'requested_end_date' => 'date',
        'approved_end_date' => 'date',
        'proposed_monthly_rent' => 'decimal:2',
        'proposed_deposit_amount' => 'decimal:2',
        'terms_snapshot' => 'array',
        'processed_at'       => 'datetime',
        'terms_offered_at' => 'datetime',
        'tenant_confirmed_at' => 'datetime',
        'tenant_declined_at' => 'datetime',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DECLINED_BY_TENANT = 'declined_by_tenant';

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->status === self::STATUS_AWAITING_CONFIRMATION;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
