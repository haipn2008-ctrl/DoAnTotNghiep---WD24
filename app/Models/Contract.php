<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Contract Status
    |--------------------------------------------------------------------------
    */

    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_EXPIRED = 'expired';

    const STATUS_TERMINATED = 'terminated';

    /*
    |--------------------------------------------------------------------------
    | Deposit Status
    |--------------------------------------------------------------------------
    */

    const DEPOSIT_PENDING = 'pending';

    const DEPOSIT_PAID = 'paid';

    const DEPOSIT_RETURNED = 'returned';

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

        'number_of_people',

        'signed_at',

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

        'status',

        'note',
    ];

    /**
     * Ép kiểu dữ liệu
     */
    protected $casts = [

        'signed_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'extended_at' => 'datetime',
        'terminated_at' => 'datetime',

        'start_date' => 'date',
        'end_date' => 'date',
        'actual_end_date' => 'date',

        'extend_start_date' => 'date',
        'extend_end_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function utilityReadings()
    {
        return $this->hasMany(UtilityReading::class, 'room_id', 'room_id');
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Invoice::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
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

    /*
    |--------------------------------------------------------------------------
    | Status Helper
    |--------------------------------------------------------------------------
    */

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

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

    public function canCreateInvoice()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canExtend()
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

    /*
    |--------------------------------------------------------------------------
    | Accessor
    |--------------------------------------------------------------------------
    */

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
}
