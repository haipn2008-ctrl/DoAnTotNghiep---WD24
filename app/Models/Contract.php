<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Contract extends Model
{
    const PARKING_MOTORCYCLE = 'motorcycle';

    const PARKING_CAR = 'car';

    /*
    |--------------------------------------------------------------------------
    | Contract Status
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_SIGNATURE = 'pending_signature';

    const STATUS_SIGNED = 'signed';

    const STATUS_DEPOSIT_PAID = 'deposit_paid';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_PENDING_DEPOSIT = 'pending_deposit';

    const STATUS_AWAITING_MOVE_IN = 'awaiting_move_in';

    const STATUS_SETTLING = 'settling';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_TERMINATED = 'terminated';

    // const STATUS_DEPOSIT_RETURNED = 'deposit_returned';

    const STATUS_COMPLETED = 'completed';

    /*
    |--------------------------------------------------------------------------
    | Deposit Status
    |--------------------------------------------------------------------------
    */

    const DEPOSIT_PENDING = 'pending';
    const DEPOSIT_PAID = 'paid';

    const DEPOSIT_REFUND_REQUESTED = 'refund_requested';
    const DEPOSIT_REFUND_APPROVED = 'refund_approved';
    const DEPOSIT_REFUND_REJECTED = 'refund_rejected';
    const DEPOSIT_REFUND_PROCESSING = 'refund_processing';

    const DEPOSIT_RETURNED = 'returned';
    const DEPOSIT_REFUNDED = 'refunded';
    const DEPOSIT_DEDUCTED = 'deducted';
    const DEPOSIT_RETAINED = 'retained';
    const DEPOSIT_NOT_REQUIRED = 'not_required';
    const DEPOSIT_PARTIAL = 'partial_returned';
    const DEPOSIT_FORFEITED = 'forfeited';

    const RESERVING_STATUSES = [
        self::STATUS_PENDING_DEPOSIT,
        self::STATUS_AWAITING_MOVE_IN,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    const OPEN_OCCUPANCY_STATUSES = [self::STATUS_ACTIVE, self::STATUS_EXPIRED];
    /**
     * Các trường được phép ghi dữ liệu
     */
    protected $fillable = [

        'contract_code',

        'room_id',
        'tenant_id',
        'representative_tenant_id',

        'monthly_rent',

        'deposit_amount',
        'deposit_status',
        'deposit_paid_at',
        'deposit_process_type',
        'deposit_refund_amount',
        'deposit_deduction_amount',
        'deposit_processed_at',
        'deposit_process_reason',
        'deposit_process_note',
        'deposit_bank_name',
        'deposit_bank_account_number',
        'deposit_bank_account_name',
        'deposit_qr_image',
        'deposit_refund_requested_at',
        'deposit_refund_approved_at',
        'deposit_transfer_amount',
        'deposit_transferred_at',
        'deposit_transfer_proof',
        'deposit_damage_proof',
        'deposit_admin_note',
        
        'number_of_people',
        'internet_enabled',
        'service_enabled',
        'parking_vehicle_type',
        'parking_quantity',

        'signed_at',
        'tenant_signature',
        'planned_move_in_date',
        'move_in_date',
        'move_in_confirmed_at',
        'move_in_confirmed_by',
        'start_date',
        'end_date',
        'actual_end_date',

        'extended_at',
        'extend_start_date',
        'extend_end_date',
        'extend_reason',
        'extend_note',

        'terminated_at',
        'terminated_by',
        'termination_reason',
        'termination_note',

        'contract_file',

        'contract_content',

        'status',

        'note',
    ];

    /**
     * Ép kiểu dữ liệu
     */
    protected $casts = [

        'signed_at'         => 'datetime',
        'planned_move_in_date' => 'date',
        'move_in_date'      => 'date',
        'move_in_confirmed_at' => 'datetime',

        'deposit_paid_at' => 'datetime',
        'deposit_processed_at' => 'datetime',
        'deposit_refund_amount' => 'decimal:2',
        'deposit_deduction_amount' => 'decimal:2',
        'deposit_refund_requested_at' => 'datetime',
        'deposit_refund_approved_at' => 'datetime',
        'deposit_transfer_amount' => 'decimal:2',
        'deposit_transferred_at' => 'datetime',

        'extended_at' => 'datetime',
        'terminated_at' => 'datetime',

        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'internet_enabled' => 'boolean',
        'service_enabled' => 'boolean',
        'parking_quantity' => 'integer',

        'extend_start_date' => 'date',
        'extend_end_date'   => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function getParkingVehicleLabelAttribute(): ?string
    {
        return match ($this->parking_vehicle_type) {
            self::PARKING_MOTORCYCLE => 'Xe máy',
            self::PARKING_CAR => 'Ô tô',
            default => null,
        };
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function representative()
    {
        return $this->belongsTo(
            Tenant::class,
            'representative_tenant_id'
        );
    }

    public function moveInConfirmedBy()
    {
        return $this->belongsTo(
            User::class,
            'move_in_confirmed_by'
        );
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function occupants()
    {
        return $this->hasMany(ContractOccupant::class);
    }

    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class, 'contract_id');
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class
        );
    }
    public function histories()
    {
        return $this->hasMany(ContractHistory::class)
            ->latest();
    }
    

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingSignature($query)
    {
        return $query->where('status', self::STATUS_PENDING_SIGNATURE);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', self::STATUS_TERMINATED);
    }
    public function scopeDepositPaid($query)
    {
        return $query->where('status', self::STATUS_DEPOSIT_PAID);
    }

    // public function scopeDepositReturned($query)
    // {
    //     return $query->where('status', self::STATUS_DEPOSIT_RETURNED);
    // }

    /*
    |--------------------------------------------------------------------------
    | Status Helper
    |--------------------------------------------------------------------------
    */

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingSignature()
    {
        return $this->status === self::STATUS_PENDING_SIGNATURE;
    }

    public function isSigned()
    {
        return $this->status === self::STATUS_SIGNED;
    }

    public function isMoveInConfirmed(): bool
    {
        return !empty($this->move_in_date)
            && !empty($this->move_in_confirmed_at)
            && !empty($this->move_in_confirmed_by);
    }

    public function isDepositPaidStatus()
    {
        return $this->status === self::STATUS_DEPOSIT_PAID;
    }

    // public function isDepositReturnedStatus()
    // {
    //     return $this->status === self::STATUS_DEPOSIT_RETURNED;
    // }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isTerminated()
    {
        return $this->status === self::STATUS_TERMINATED;
    }
    

    /*
    |--------------------------------------------------------------------------
    | Deposit Helper
    |--------------------------------------------------------------------------
    */

    public function isDepositPending()
    {
        return $this->deposit_status === self::DEPOSIT_PENDING;
    }

    public function isDepositPaid()
    {
        return $this->deposit_status === self::DEPOSIT_PAID;
    }

    public function isDepositReturned()
    {
        return $this->deposit_status === self::DEPOSIT_RETURNED;
    }

    /*
    |--------------------------------------------------------------------------
    | Business Helper
    |--------------------------------------------------------------------------
    */

    public function canRecordUtility()
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->deposit_status === self::DEPOSIT_PAID;
    }

    public function contractFileExists(): bool
    {
        return filled($this->contract_file) && Storage::disk('local')->exists($this->contract_file);
    }

    public function canCreateInvoice()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canExtend(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_EXPIRED,
        ]);
    }

    public function canTerminate()
    {
        return $this->status === self::STATUS_ACTIVE;
    }
        
    public function canActivate()
    {
        return $this->status === self::STATUS_DEPOSIT_PAID;
    }
    public function canReturnDeposit(): bool
    {
        return $this->status === self::STATUS_TERMINATED
            && in_array($this->deposit_status, [
                self::DEPOSIT_REFUND_REQUESTED,
                self::DEPOSIT_REFUND_APPROVED,
                self::DEPOSIT_REFUND_PROCESSING,
            ], true);
    }

    public function canRequestDepositRefund(): bool
    {
        return $this->status === self::STATUS_TERMINATED
            && in_array($this->deposit_status, [
                self::DEPOSIT_PAID,
                self::DEPOSIT_REFUND_REJECTED,
            ], true);
    }

    public function isRefundRequested(): bool
    {
        return $this->deposit_status === self::DEPOSIT_REFUND_REQUESTED;
    }

    public function isRefundApproved(): bool
    {
        return $this->deposit_status === self::DEPOSIT_REFUND_APPROVED;
    }

    public function isRefundCompleted(): bool
    {
        return in_array($this->deposit_status, [
            self::DEPOSIT_RETURNED,
            self::DEPOSIT_PARTIAL,
            self::DEPOSIT_FORFEITED,
        ], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canEdit()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PENDING_SIGNATURE => 'Chờ ký',
            self::STATUS_SIGNED => 'Đã ký',
            self::STATUS_DEPOSIT_PAID => 'Đã thanh toán cọc',
            self::STATUS_ACTIVE => 'Đang hoạt động',
            self::STATUS_EXPIRED => 'Hết hạn',
            self::STATUS_TERMINATED => 'Đã kết thúc',
            // self::STATUS_DEPOSIT_RETURNED => 'Đã hoàn cọc',
            self::STATUS_COMPLETED => 'Hoàn tất',
            default => 'Không xác định',
        };
    }
    public function getDurationAttribute()
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function isNearExpired($days = 30)
    {
        return now()->diffInDays(
            $this->end_date,
            false
        ) <= $days;
    }

    public function isOverExpired()
    {
        return now()->greaterThan($this->end_date);
    }
    public function extensionRequests()
    {
        return $this->hasMany(ContractExtensionRequest::class);
    }
    public function getDepositStatusTextAttribute(): string
    {
        return match ($this->deposit_status) {
            self::DEPOSIT_PENDING => 'Chưa đóng cọc',
            self::DEPOSIT_PAID => 'Đã đóng cọc',
            self::DEPOSIT_REFUND_REQUESTED => 'Chờ duyệt hoàn cọc',
            self::DEPOSIT_REFUND_APPROVED => 'Đã duyệt hoàn cọc',
            self::DEPOSIT_REFUND_REJECTED => 'Từ chối hoàn cọc',
            self::DEPOSIT_REFUND_PROCESSING => 'Đang chuyển khoản',
            self::DEPOSIT_RETURNED => 'Đã hoàn toàn bộ',
            self::DEPOSIT_PARTIAL => 'Đã hoàn một phần',
            self::DEPOSIT_FORFEITED => 'Không hoàn cọc',
            default => 'Không xác định',
        };
    }
}
