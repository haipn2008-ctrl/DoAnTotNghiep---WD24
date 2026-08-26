<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LogicException;

class Contract extends Model
{
    /**
     * Chỉ người thuê đại diện mới được quản lý hợp đồng trên cổng khách thuê.
     */
    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        $tenantId = $user->tenant?->id;

        return $tenantId
            ? $query->where('tenant_id', $tenantId)
            : $query->whereRaw('1 = 0');
    }

    public function isManagedBy(User $user): bool
    {
        return $user->tenant !== null
            && (int) $this->tenant_id === (int) $user->tenant->id;
    }

    /*
    |--------------------------------------------------------------------------
    | Parking
    |--------------------------------------------------------------------------
    */

    public const PARKING_MOTORCYCLE = 'motorcycle';

    public const PARKING_CAR = 'car';

    /*
    |--------------------------------------------------------------------------
    | Contract Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_SIGNATURE = 'pending_signature';

    public const SIGNATURE_DEADLINE_DAYS = 3;

    public const STATUS_PENDING_DEPOSIT = 'pending_deposit';

    public const STATUS_AWAITING_MOVE_IN = 'awaiting_move_in';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SETTLING = 'settling';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Legacy status aliases
    |--------------------------------------------------------------------------
    |
    | Giữ lại để các phần code cũ không bị lỗi trong quá trình chuyển đổi.
    |
    */

    public const STATUS_PENDING = self::STATUS_PENDING_SIGNATURE;

    public const STATUS_TERMINATED = self::STATUS_SETTLING;

    public const STATUS_SIGNED = self::STATUS_PENDING_SIGNATURE;

    public const STATUS_DEPOSIT_PAID = self::STATUS_PENDING_DEPOSIT;

    /*
    |--------------------------------------------------------------------------
    | Deposit Status
    |--------------------------------------------------------------------------
    */

    public const DEPOSIT_PENDING = 'pending';

    public const DEPOSIT_PAID = 'paid';

    public const DEPOSIT_NEEDS_RESOLUTION = 'needs_resolution';

    public const DEPOSIT_RETURNED = 'returned';

    public const DEPOSIT_REFUNDED = 'refunded';

    public const DEPOSIT_DEDUCTED = 'deducted';

    public const DEPOSIT_RETAINED = 'retained';

    public const DEPOSIT_NOT_REQUIRED = 'not_required';

    /*
    | Legacy deposit statuses
    */

    public const DEPOSIT_REFUND_REQUESTED = 'refund_requested';

    public const DEPOSIT_REFUND_APPROVED = 'refund_approved';

    public const DEPOSIT_REFUND_REJECTED = 'refund_rejected';

    public const DEPOSIT_REFUND_PROCESSING = 'refund_processing';

    public const DEPOSIT_PARTIAL = 'partial_returned';

    public const DEPOSIT_FORFEITED = 'forfeited';

    /*
    |--------------------------------------------------------------------------
    | Status Groups
    |--------------------------------------------------------------------------
    */

    public const RESERVING_STATUSES = [
        self::STATUS_PENDING_SIGNATURE,
        self::STATUS_PENDING_DEPOSIT,
        self::STATUS_AWAITING_MOVE_IN,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    public const OPEN_OCCUPANCY_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
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

        'checkout_key_count',

        'checkout_asset_report',

        'checkout_damage_note',

        'checkout_photo_paths',

        'checkout_handover_confirmed_at',

        'internet_enabled',

        'service_enabled',

        'parking_vehicle_type',

        'parking_quantity',

        'tenant_signature',

        'planned_move_in_date',

        'move_in_date',

        'move_in_confirmed_at',

        'move_in_confirmed_by',

        'start_date',

        'end_date',

        'rental_duration_option',

        'signature_due_at',

        'deposit_due_at',

        'scheduled_move_in_date',

        'reservation_expires_at',

        'contract_file',

        'contract_content',

        'note',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'signed_at' => 'datetime',

        'signature_due_at' => 'datetime',

        'deposit_due_at' => 'datetime',

        'deposit_paid_at' => 'datetime',

        'deposit_processed_at' => 'datetime',

        'deposit_refund_requested_at' => 'datetime',

        'deposit_refund_approved_at' => 'datetime',

        'deposit_transferred_at' => 'datetime',

        'scheduled_move_in_date' => 'date',

        'reservation_expires_at' => 'datetime',

        'planned_move_in_date' => 'date',

        'move_in_date' => 'date',

        'move_in_confirmed_at' => 'datetime',

        'move_in_terms_confirmed_at' => 'datetime',

        'move_in_inventory_snapshotted_at' => 'datetime',

        'move_in_details_confirmed_at' => 'datetime',

        'actual_move_in_at' => 'datetime',

        'actual_move_out_at' => 'datetime',

        'checkout_asset_report' => 'array',

        'checkout_photo_paths' => 'array',

        'checkout_key_count' => 'integer',

        'checkout_handover_confirmed_at' => 'datetime',

        'scheduled_move_out_at' => 'datetime',

        'actual_end_date' => 'date',

        'cancelled_at' => 'datetime',

        'completed_at' => 'datetime',

        'deposit_resolved_at' => 'datetime',

        'extended_at' => 'datetime',

        'terminated_at' => 'datetime',

        'start_date' => 'date',

        'end_date' => 'date',

        'extend_start_date' => 'date',

        'extend_end_date' => 'date',

        'internet_enabled' => 'boolean',

        'service_enabled' => 'boolean',

        'parking_quantity' => 'integer',

        'number_of_people' => 'integer',

        'monthly_rent' => 'decimal:2',

        'deposit_amount' => 'decimal:2',

        'deposit_refund_amount' => 'decimal:2',

        'deposit_deduction_amount' => 'decimal:2',

        'deposit_transfer_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException(
                'Không được xóa hợp đồng. Hãy dùng hành động hủy để giữ lịch sử.'
            );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Phòng thuê
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Khách thuê đứng tên hợp đồng
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Khách thuê đại diện
    public function representative()
    {
        return $this->belongsTo(
            Tenant::class,
            'representative_tenant_id'
        );
    }

    // Danh sách người thuê trong hợp đồng
    public function members()
    {
        return $this->hasMany(ContractTenant::class);
    }

    // Danh sách người đang còn hiệu lực trên hợp đồng; các bản cũ vẫn nằm trong lịch sử.
    public function currentMembers()
    {
        return $this->hasMany(ContractTenant::class)->current();
    }

    // Người xác nhận nhận phòng
    public function moveInConfirmedBy()
    {
        return $this->belongsTo(
            User::class,
            'move_in_confirmed_by'
        );
    }

    // Thành viên thuê giữ vai trò đại diện
    public function representativeMember()
    {
        return $this->hasOne(ContractTenant::class)
            ->ofMany(['id' => 'max'], fn ($query) => $query->where(
                'role',
                ContractTenant::ROLE_REPRESENTATIVE
            ));
    }

    public function representativeTransfers()
    {
        return $this->hasMany(ContractRepresentativeTransfer::class)->latest('effective_at')->latest('id');
    }

    // Hóa đơn
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Chỉ số điện nước
    public function utilityReadings()
    {
        return $this->hasMany(
            UtilityReading::class,
            'contract_id'
        );
    }

    // Thanh toán thông qua hóa đơn
    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class
        );
    }

    // Lịch sử hợp đồng
    public function histories()
    {
        return $this->hasMany(ContractHistory::class)
            ->latest();
    }

    // Yêu cầu gia hạn hợp đồng
    public function extensionRequests()
    {
        return $this->hasMany(
            ContractExtensionRequest::class
        );
    }

    public function terminationRequests()
    {
        return $this->hasMany(ContractTerminationRequest::class);
    }

    public function approvedTerminationRequest()
    {
        return $this->belongsTo(ContractTerminationRequest::class, 'approved_termination_request_id');
    }

    public function settlementStatement()
    {
        return $this->hasOne(SettlementStatement::class);
    }

    // Hồ sơ đăng ký tạm trú
    public function temporaryResidences()
    {
        return $this->hasMany(
            TemporaryResidence::class
        );
    }

    // Lịch sử trạng thái
    public function statusHistories()
    {
        return $this->hasMany(
            ContractStatusHistory::class
        )
            ->orderBy('performed_at')
            ->orderBy('id');
    }

    // Cảnh báo vòng đời hợp đồng
    public function lifecycleAlerts()
    {
        return $this->hasMany(
            ContractLifecycleAlert::class
        );
    }

    // Danh sách bàn giao
    public function handoverItems()
    {
        return $this->hasMany(
            ContractHandoverItem::class
        )
            ->orderBy('name')
            ->orderBy('id');
    }

    // Người xác nhận ký
    public function signedConfirmer()
    {
        return $this->belongsTo(
            User::class,
            'signed_confirmed_by'
        );
    }

    // Người xác nhận điều khoản nhận phòng
    public function moveInTermsConfirmer()
    {
        return $this->belongsTo(
            User::class,
            'move_in_terms_confirmed_by'
        );
    }

    // Người xác nhận thông tin nhận phòng
    public function moveInDetailsConfirmer()
    {
        return $this->belongsTo(
            User::class,
            'move_in_details_confirmed_by'
        );
    }

    // Người check-in
    public function checkedInBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }

    // Người check-out
    public function checkedOutBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_out_by'
        );
    }

    // Người hủy
    public function cancelledBy()
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    // Người hoàn tất
    public function completedBy()
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where(
            'status',
            self::STATUS_DRAFT
        );
    }

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            self::STATUS_ACTIVE
        );
    }

    public function scopeOccupying($query)
    {
        return $query->whereIn(
            'status',
            self::OPEN_OCCUPANCY_STATUSES
        );
    }

    public function scopeReserving($query)
    {
        return $query->whereIn(
            'status',
            self::RESERVING_STATUSES
        );
    }

    public function scopeDepositPaid($query)
    {
        return $query->where(
            'deposit_status',
            self::DEPOSIT_PAID
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingSignature(): bool
    {
        return $this->status === self::STATUS_PENDING_SIGNATURE;
    }

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_PENDING_SIGNATURE;
    }

    public function isMoveInConfirmed(): bool
    {
        return ! empty($this->move_in_date)
            && ! empty($this->move_in_confirmed_at)
            && ! empty($this->move_in_confirmed_by);
    }

    public function isDepositPaidStatus(): bool
    {
        return $this->deposit_status === self::DEPOSIT_PAID;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_SETTLING;
    }

    /*
    |--------------------------------------------------------------------------
    | Deposit Helpers
    |--------------------------------------------------------------------------
    */

    public function isDepositPending(): bool
    {
        return $this->deposit_status === self::DEPOSIT_PENDING;
    }

    public function isDepositPaid(): bool
    {
        return $this->deposit_status === self::DEPOSIT_PAID;
    }

    public function canReturnDeposit(): bool
    {
        return $this->status === self::STATUS_SETTLING
            && in_array(
                $this->deposit_status,
                [
                    self::DEPOSIT_REFUND_REQUESTED,
                    self::DEPOSIT_REFUND_APPROVED,
                    self::DEPOSIT_REFUND_PROCESSING,
                ],
                true
            );
    }

    public function isRefundRequested(): bool
    {
        return $this->deposit_status === self::DEPOSIT_REFUND_REQUESTED;
    }

    public function isRefundApproved(): bool
    {
        return $this->status === self::STATUS_SETTLING
            && in_array($this->deposit_status, [
                self::DEPOSIT_REFUND_APPROVED,
                self::DEPOSIT_REFUND_PROCESSING,
            ], true);
    }

    public function isRefundCompleted(): bool
    {
        return (float) $this->deposit_refund_amount > 0
            && $this->deposit_transferred_at !== null
            && filled($this->deposit_transfer_proof)
            && in_array($this->deposit_resolution, [
                self::DEPOSIT_REFUNDED,
                self::DEPOSIT_DEDUCTED,
            ], true);
    }

    public function canRequestDepositRefund(): bool
    {
        $statement = $this->relationLoaded('settlementStatement')
            ? $this->settlementStatement
            : $this->settlementStatement()->first();

        return $this->status === self::STATUS_SETTLING
            && (float) $this->deposit_amount > 0
            && $statement
            && (float) $statement->net_amount < 0
            && in_array($this->deposit_status, [
                self::DEPOSIT_PAID,
                self::DEPOSIT_NEEDS_RESOLUTION,
                self::DEPOSIT_REFUND_REJECTED,
            ], true)
            && ! in_array($this->deposit_resolution, [
                self::DEPOSIT_REFUNDED,
                self::DEPOSIT_DEDUCTED,
                self::DEPOSIT_RETAINED,
            ], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle Helpers
    |--------------------------------------------------------------------------
    */

    public function isReservationOverdue(): bool
    {
        return $this->status === self::STATUS_AWAITING_MOVE_IN
            && $this->reservation_expires_at?->isPast();
    }

    public function isSignatureOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING_SIGNATURE
            && $this->signature_due_at?->isPast();
    }

    public function isDepositOverdue(): bool
    {
        return $this->status === self::STATUS_PENDING_DEPOSIT
            && $this->deposit_due_at?->isPast();
    }

    public function canRecordUtility(): bool
    {
        return in_array(
            $this->status,
            self::OPEN_OCCUPANCY_STATUSES,
            true
        );
    }

    public function canCreateInvoice(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_ACTIVE,
                self::STATUS_EXPIRED,
                self::STATUS_SETTLING,
            ],
            true
        );
    }

    public function canExtend(): bool
    {
        return in_array(
            $this->status,
            self::OPEN_OCCUPANCY_STATUSES,
            true
        );
    }

    public function canTerminate(): bool
    {
        return in_array(
            $this->status,
            self::OPEN_OCCUPANCY_STATUSES,
            true
        );
    }

    public function canActivate(): bool
    {
        return $this->status === self::STATUS_PENDING_DEPOSIT;
    }

    /*
    |--------------------------------------------------------------------------
    | Contract File
    |--------------------------------------------------------------------------
    */

    public function contractFileExists(): bool
    {
        return filled($this->contract_file)
            && Storage::disk('local')->exists(
                $this->contract_file
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Deposit Amount
    |--------------------------------------------------------------------------
    */

    public function getDepositPaidAmountAttribute(): float
    {
        $depositInvoice = $this->relationLoaded('invoices')
            ? $this->invoices->firstWhere(
                'invoice_type',
                Invoice::TYPE_DEPOSIT
            )
            : $this->invoices()
                ->where(
                    'invoice_type',
                    Invoice::TYPE_DEPOSIT
                )
                ->first();

        return $depositInvoice
            ? (float) $depositInvoice
                ->payments()
                ->success()
                ->sum('amount_paid')
            : 0.0;
    }

    public function getDepositRemainingAmountAttribute(): float
    {
        return max(
            0,
            (float) $this->deposit_amount
                - $this->deposit_paid_amount
        );
    }

    /*
    |--------------------------------------------------------------------------
    | First Month Rent
    |--------------------------------------------------------------------------
    */

    public function getFirstMonthRentPaidAmountAttribute(): float
    {
        $invoice = $this->relationLoaded('invoices')
            ? $this->invoices->firstWhere(
                'invoice_type',
                Invoice::TYPE_FIRST_MONTH_RENT
            )
            : $this->invoices()
                ->where(
                    'invoice_type',
                    Invoice::TYPE_FIRST_MONTH_RENT
                )
                ->first();

        return $invoice
            ? (float) $invoice
                ->payments()
                ->success()
                ->sum('amount_paid')
            : 0.0;
    }

    public function getFirstMonthRentDaysAttribute(): int
    {
        if (! $this->start_date) {
            return 0;
        }

        return $this->start_date->daysInMonth
            - $this->start_date->day
            + 1;
    }

    public function getCalculatedFirstMonthRentAmountAttribute(): float
    {
        $days = $this->first_month_rent_days;

        if ($days <= 5 || ! $this->start_date) {
            return 0.0;
        }

        return (float) round(
            (float) $this->monthly_rent
                * $days
                / $this->start_date->daysInMonth
        );
    }

    public function getFirstMonthRentAmountAttribute(): float
    {
        $invoice = $this->relationLoaded('invoices')
            ? $this->invoices->firstWhere(
                'invoice_type',
                Invoice::TYPE_FIRST_MONTH_RENT
            )
            : $this->invoices()
                ->where(
                    'invoice_type',
                    Invoice::TYPE_FIRST_MONTH_RENT
                )
                ->first();

        return $invoice
            ? (float) $invoice->total_amount
            : $this->calculated_first_month_rent_amount;
    }

    public function getFirstMonthRentRemainingAmountAttribute(): float
    {
        return max(
            0,
            $this->first_month_rent_amount
                - $this->first_month_rent_paid_amount
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Bản nháp',

            self::STATUS_PENDING_SIGNATURE => 'Chờ ký',

            self::STATUS_PENDING_DEPOSIT => 'Chờ tiền cọc',

            self::STATUS_AWAITING_MOVE_IN => 'Chờ nhận phòng',

            self::STATUS_ACTIVE => 'Đang ở',

            self::STATUS_EXPIRED => 'Quá hạn hợp đồng',

            self::STATUS_SETTLING => 'Đang quyết toán',

            self::STATUS_COMPLETED => 'Đã hoàn tất',

            self::STATUS_CANCELLED => 'Đã hủy',

            default => $this->status ?? 'Không xác định',
        };
    }

    public function getDepositStatusTextAttribute(): string
    {
        return match ($this->deposit_status) {
            self::DEPOSIT_PENDING => 'Chưa đóng cọc',

            self::DEPOSIT_PAID => 'Đã đóng cọc',

            self::DEPOSIT_NEEDS_RESOLUTION => 'Cần xử lý',

            self::DEPOSIT_REFUND_REQUESTED => 'Chờ duyệt hoàn cọc',

            self::DEPOSIT_REFUND_APPROVED => 'Đã duyệt hoàn cọc',

            self::DEPOSIT_REFUND_REJECTED => 'Từ chối hoàn cọc',

            self::DEPOSIT_REFUND_PROCESSING => 'Đang chuyển khoản',

            self::DEPOSIT_RETURNED => 'Đã hoàn cọc',

            self::DEPOSIT_PARTIAL => 'Đã hoàn một phần',

            self::DEPOSIT_FORFEITED => 'Không hoàn cọc',

            self::DEPOSIT_DEDUCTED => 'Đã khấu trừ',

            self::DEPOSIT_RETAINED => 'Đã giữ cọc',

            self::DEPOSIT_REFUNDED => 'Đã hoàn tiền',

            self::DEPOSIT_NOT_REQUIRED => 'Không yêu cầu cọc',

            default => 'Không xác định',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Duration
    |--------------------------------------------------------------------------
    */

    public function getDurationAttribute()
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        return $this->start_date->diffInMonths(
            $this->end_date
        );
    }

    public function isNearExpired(int $days = 30): bool
    {
        return in_array(
            $this->status,
            self::OPEN_OCCUPANCY_STATUSES,
            true
        )
            && $this->end_date
            && now()->diffInDays(
                $this->end_date,
                false
            ) <= $days;
    }

    public function isOverExpired(): bool
    {
        return $this->end_date
            && now()
                ->startOfDay()
                ->gt(
                    $this->end_date->startOfDay()
                );
    }
}
