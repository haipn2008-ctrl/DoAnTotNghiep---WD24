<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Invoice Status
    |--------------------------------------------------------------------------
    */

    const STATUS_UNPAID = 'unpaid';

    const STATUS_PARTIAL = 'partial';

    const STATUS_PAID = 'paid';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'contract_id',

        'utility_reading_id',

        'month',

        'year',

        'invoice_date',

        'due_date',

        'room_fee',

        'electricity_fee',

        'water_fee',

        'internet_fee',

        'service_fee',

        'total_amount',

        'status',

    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'invoice_date' => 'date',

        'due_date' => 'date',

        'room_fee' => 'decimal:2',

        'electricity_fee' => 'decimal:2',

        'water_fee' => 'decimal:2',

        'internet_fee' => 'decimal:2',

        'service_fee' => 'decimal:2',

        'total_amount' => 'decimal:2',

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

    public function details()
    {
        return $this->hasMany(InvoiceDetail::class)
            ->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
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
        return max(
            0,
            $this->total_amount - $this->paid_amount
        );
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {

            self::STATUS_PAID => 'Đã thanh toán',

            self::STATUS_PARTIAL => 'Thanh toán một phần',

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

    /**
     * Hóa đơn có thể nhận thêm thanh toán không
     */
    public function canPay(): bool
    {
        return $this->status !== self::STATUS_PAID;
    }

    public function isOverdue()
    {
        return
            now()->gt($this->due_date)
            &&
            !$this->isPaid();
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
        $paid = $this->payments()
            ->success()
            ->sum('amount_paid');

        if ($paid >= $this->total_amount && $this->total_amount > 0) {

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
