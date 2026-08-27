<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    const TYPE_RENTAL = 'rental';

    const TYPE_DEPOSIT = 'deposit';

    const TYPE_FIRST_MONTH_RENT = 'first_month_rent';

    const TYPE_SETTLEMENT = 'settlement';

    /*
    |--------------------------------------------------------------------------
    | Invoice Status
    |--------------------------------------------------------------------------
    */

    const STATUS_UNPAID = 'unpaid';

    const STATUS_PARTIAL = 'partial';

    const STATUS_PAID = 'paid';

    const STATUS_WRITTEN_OFF = 'written_off';

    const STATUS_CANCELLED = 'cancelled';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'contract_id',

        'fee_schedule_id',

        'invoice_code',

        'invoice_type',

        'revision',

        'room_id',

        'utility_reading_id',

        'month',

        'year',

        'invoice_date',

        'due_date',

        'due_notified_at',

        'overdue_notified_at',

        'payment_extension_until',

        'room_fee',

        'electricity_fee',

        'water_fee',

        'internet_fee',

        'service_fee',

        'total_amount',

        'adjustment_amount',

        'status',

        'cancelled_at',

        'cancelled_by',

        'cancellation_reason',

        'invoice_type',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'invoice_date' => 'date',

        'due_date' => 'date',

        'due_notified_at' => 'datetime',

        'overdue_notified_at' => 'datetime',

        'payment_extension_until' => 'date',

        'room_fee' => 'decimal:2',

        'electricity_fee' => 'decimal:2',

        'water_fee' => 'decimal:2',

        'internet_fee' => 'decimal:2',

        'service_fee' => 'decimal:2',

        'total_amount' => 'decimal:2',

        'adjustment_amount' => 'decimal:2',

        'revision' => 'integer',

        'cancelled_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function utilityReading()
    {
        return $this->belongsTo(UtilityReading::class);
    }

    public function feeSchedule()
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class)
            ->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function adjustments()
    {
        return $this->hasMany(InvoiceAdjustment::class)->orderBy('id');
    }

    public function reminders()
    {
        return $this->hasMany(InvoiceReminder::class)->latest('reminded_at')->latest('id');
    }

    public function latestReminder()
    {
        return $this->hasOne(InvoiceReminder::class)->latestOfMany('reminded_at');
    }

    public function paymentDelayRequests()
    {
        return $this->hasMany(InvoicePaymentDelayRequest::class)->latest('id');
    }

    public function pendingPaymentDelayRequest()
    {
        return $this->hasOne(InvoicePaymentDelayRequest::class)
            ->where('status', InvoicePaymentDelayRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public function scopeUnpaid($query)
    {
        return $query->where(
            'status',
            self::STATUS_UNPAID
        );
    }

    public function scopePartial($query)
    {
        return $query->where(
            'status',
            self::STATUS_PARTIAL
        );
    }

    public function scopePaid($query)
    {
        return $query->where(
            'status',
            self::STATUS_PAID
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

    public function getPaidAmountAttribute()
    {
        return $this->payments()
            ->success()
            ->sum('amount_paid');
    }

    public function getRemainingAmountAttribute()
    {
        if (in_array($this->status, [self::STATUS_WRITTEN_OFF, self::STATUS_CANCELLED], true)) {
            return 0.0;
        }

        return max(
            0,
            $this->payable_amount - $this->paid_amount
        );
    }

    public function getPayableAmountAttribute(): float
    {
        // The application collects VND as whole dong. Always round the final
        // payable amount so fractional prorated charges cannot leave an
        // unpayable balance smaller than one dong.
        return max(0, round((float) $this->total_amount + (float) $this->adjustment_amount));
    }

    public function getOverpaidAmountAttribute(): float
    {
        return max(0, (float) $this->paid_amount - $this->payable_amount);
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->canPay() || ! $this->effective_due_date || ! today()->gt($this->effective_due_date)) {
            return 0;
        }

        return (int) $this->effective_due_date->diffInDays(today());
    }

    public function getEffectiveDueDateAttribute()
    {
        return $this->payment_extension_until ?: $this->due_date;
    }

    public function getDebtBucketAttribute(): string
    {
        if (! $this->canPay()) {
            return 'settled';
        }

        if ($this->effective_due_date->isFuture()) {
            return 'upcoming';
        }

        if ($this->effective_due_date->isToday()) {
            return 'due_today';
        }

        return match (true) {
            $this->days_overdue <= 3 => 'overdue_1_3',
            $this->days_overdue <= 7 => 'overdue_4_7',
            $this->days_overdue <= 14 => 'overdue_8_14',
            default => 'overdue_15_plus',
        };
    }

    public function getDebtBucketLabelAttribute(): string
    {
        return match ($this->debt_bucket) {
            'upcoming' => 'Chưa đến hạn',
            'due_today' => 'Đến hạn hôm nay',
            'overdue_1_3' => 'Quá hạn 1–3 ngày',
            'overdue_4_7' => 'Quá hạn 4–7 ngày',
            'overdue_8_14' => 'Quá hạn 8–14 ngày',
            'overdue_15_plus' => 'Quá hạn từ 15 ngày',
            default => 'Đã xử lý',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {

            self::STATUS_PAID => 'Đã thanh toán',

            self::STATUS_PARTIAL => 'Thanh toán một phần',

            self::STATUS_WRITTEN_OFF => 'Đã xóa nợ có phê duyệt',

            self::STATUS_CANCELLED => 'Đã hủy',

            default => 'Chưa thanh toán',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPartial()
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isUnpaid()
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFirstMonthRent(): bool
    {
        return $this->invoice_type === self::TYPE_FIRST_MONTH_RENT;
    }

    public function isDeposit(): bool
    {
        return $this->invoice_type === self::TYPE_DEPOSIT;
    }

    /**
     * Hóa đơn có thể nhận thêm thanh toán không
     */
    public function canPay(): bool
    {
        return ! in_array($this->status, [self::STATUS_PAID, self::STATUS_WRITTEN_OFF, self::STATUS_CANCELLED], true);
    }

    public function isOverdue()
    {
        return $this->days_overdue > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Total
    |--------------------------------------------------------------------------
    */

    public function calculateTotal()
    {
        $this->total_amount = $this->details()
            ->sum('amount');

        return $this->total_amount;
    }

    public function refreshTotal()
    {
        $this->calculateTotal();

        $this->save();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Status
    |--------------------------------------------------------------------------
    */

    public function refreshStatus()
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_WRITTEN_OFF], true)) {
            return $this;
        }

        $paid = $this->payments()
            ->success()
            ->sum('amount_paid');
        $payable = $this->payable_amount;

        if ($payable <= 0 || $paid >= $payable) {

            $this->status = self::STATUS_PAID;
        } elseif ($paid > 0) {

            $this->status = self::STATUS_PARTIAL;
        } else {

            $this->status = self::STATUS_UNPAID;
        }

        $this->save();

        return $this;
    }
}
