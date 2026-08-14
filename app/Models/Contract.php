<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use LogicException;

class Contract extends Model
{
    public const PARKING_MOTORCYCLE = 'motorcycle';

    public const PARKING_CAR = 'car';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_SIGNATURE = 'pending_signature';

    public const STATUS_PENDING_DEPOSIT = 'pending_deposit';

    public const STATUS_AWAITING_MOVE_IN = 'awaiting_move_in';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SETTLING = 'settling';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Tên cũ được giữ để code tích hợp có thời gian chuyển đổi. */
    public const STATUS_PENDING = self::STATUS_PENDING_SIGNATURE;

    public const STATUS_TERMINATED = self::STATUS_SETTLING;

    public const DEPOSIT_PENDING = 'pending';

    public const DEPOSIT_PAID = 'paid';

    public const DEPOSIT_NEEDS_RESOLUTION = 'needs_resolution';

    public const DEPOSIT_RETURNED = 'returned';

    public const DEPOSIT_REFUNDED = 'refunded';

    public const DEPOSIT_DEDUCTED = 'deducted';

    public const DEPOSIT_RETAINED = 'retained';

    public const DEPOSIT_NOT_REQUIRED = 'not_required';

    public const RESERVING_STATUSES = [
        self::STATUS_PENDING_DEPOSIT,
        self::STATUS_AWAITING_MOVE_IN,
        self::STATUS_ACTIVE,
        self::STATUS_EXPIRED,
    ];

    public const OPEN_OCCUPANCY_STATUSES = [self::STATUS_ACTIVE, self::STATUS_EXPIRED];

    /**
     * Chỉ dữ liệu nội dung được mass assign. Status và toàn bộ audit fields phải được
     * ContractLifecycleService ghi bằng forceFill bên trong transaction.
     */
    protected $fillable = [
        'contract_code',
        'room_id',
        'tenant_id',
        'representative_tenant_id',
        'representative_is_occupant',
        'monthly_rent',
        'deposit_amount',
        'number_of_people',
        'internet_enabled',
        'service_enabled',
        'parking_vehicle_type',
        'parking_quantity',
        'start_date',
        'end_date',
        'rental_duration_option',
        'signature_due_at',
        'deposit_due_at',
        'scheduled_move_in_date',
        'reservation_expires_at',
        'contract_file',
        'note',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'signature_due_at' => 'datetime',
        'deposit_due_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'scheduled_move_in_date' => 'date',
        'reservation_expires_at' => 'datetime',
        'move_in_terms_confirmed_at' => 'datetime',
        'move_in_inventory_snapshotted_at' => 'datetime',
        'move_in_details_confirmed_at' => 'datetime',
        'actual_move_in_at' => 'datetime',
        'actual_move_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'deposit_resolved_at' => 'datetime',
        'extended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',
        'extend_start_date' => 'date',
        'extend_end_date' => 'date',
        'internet_enabled' => 'boolean',
        'service_enabled' => 'boolean',
        'parking_quantity' => 'integer',
        'number_of_people' => 'integer',
        'representative_is_occupant' => 'boolean',
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    public function getParkingVehicleLabelAttribute(): ?string
    {
        return match ($this->parking_vehicle_type) {
            self::PARKING_MOTORCYCLE => 'Xe máy',
            self::PARKING_CAR => 'Ô tô',
            default => null,
        };
    }

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('Không được xóa hợp đồng. Hãy dùng hành động hủy để giữ lịch sử.');
        });
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
        return $this->belongsTo(Tenant::class, 'representative_tenant_id');
    }

    public function occupants()
    {
        return $this->hasMany(ContractOccupant::class);
    }

    public function representativeOccupant()
    {
        return $this->hasOne(ContractOccupant::class)
            ->where('role', ContractOccupant::ROLE_REPRESENTATIVE)
            ->latestOfMany();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class, 'contract_id');
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ContractStatusHistory::class)->orderBy('performed_at')->orderBy('id');
    }

    public function lifecycleAlerts()
    {
        return $this->hasMany(ContractLifecycleAlert::class);
    }

    public function handoverItems()
    {
        return $this->hasMany(ContractHandoverItem::class)->orderBy('name')->orderBy('id');
    }

    public function signedConfirmer()
    {
        return $this->belongsTo(User::class, 'signed_confirmed_by');
    }

    public function moveInTermsConfirmer()
    {
        return $this->belongsTo(User::class, 'move_in_terms_confirmed_by');
    }

    public function moveInDetailsConfirmer()
    {
        return $this->belongsTo(User::class, 'move_in_details_confirmed_by');
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy()
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOccupying($query)
    {
        return $query->whereIn('status', self::OPEN_OCCUPANCY_STATUSES);
    }

    public function scopeReserving($query)
    {
        return $query->whereIn('status', self::RESERVING_STATUSES);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isDepositPaid(): bool
    {
        return $this->deposit_status === self::DEPOSIT_PAID;
    }

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
        return in_array($this->status, self::OPEN_OCCUPANCY_STATUSES, true);
    }

    public function canCreateInvoice(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_SETTLING], true);
    }

    public function canExtend(): bool
    {
        return in_array($this->status, self::OPEN_OCCUPANCY_STATUSES, true);
    }

    public function canTerminate(): bool
    {
        return in_array($this->status, self::OPEN_OCCUPANCY_STATUSES, true);
    }

    public function contractFileExists(): bool
    {
        return filled($this->contract_file) && Storage::disk('local')->exists($this->contract_file);
    }

    public function getDepositPaidAmountAttribute(): float
    {
        $depositInvoice = $this->relationLoaded('invoices')
            ? $this->invoices->firstWhere('invoice_type', Invoice::TYPE_DEPOSIT)
            : $this->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->first();

        return $depositInvoice ? (float) $depositInvoice->payments()->success()->sum('amount_paid') : 0.0;
    }

    public function getDepositRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->deposit_amount - $this->deposit_paid_amount);
    }

    public function getFirstMonthRentPaidAmountAttribute(): float
    {
        $invoice = $this->relationLoaded('invoices')
            ? $this->invoices->firstWhere('invoice_type', Invoice::TYPE_FIRST_MONTH_RENT)
            : $this->invoices()->where('invoice_type', Invoice::TYPE_FIRST_MONTH_RENT)->first();

        return $invoice ? (float) $invoice->payments()->success()->sum('amount_paid') : 0.0;
    }

    public function getFirstMonthRentRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->monthly_rent - $this->first_month_rent_paid_amount);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PENDING_SIGNATURE => 'Chờ ký',
            self::STATUS_PENDING_DEPOSIT => 'Chờ cọc và tiền phòng tháng đầu',
            self::STATUS_AWAITING_MOVE_IN => 'Chờ nhận phòng',
            self::STATUS_ACTIVE => 'Đang ở',
            self::STATUS_EXPIRED => 'Quá hạn hợp đồng',
            self::STATUS_SETTLING => 'Đang quyết toán',
            self::STATUS_COMPLETED => 'Đã hoàn tất',
            self::STATUS_CANCELLED => 'Đã hủy',
            default => $this->status,
        };
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function isNearExpired(int $days = 30): bool
    {
        return in_array($this->status, self::OPEN_OCCUPANCY_STATUSES, true)
            && now()->diffInDays($this->end_date, false) <= $days;
    }

    public function isOverExpired(): bool
    {
        return now()->startOfDay()->gt($this->end_date->startOfDay());
    }
}
