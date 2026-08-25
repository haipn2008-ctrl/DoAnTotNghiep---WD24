<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceAdjustment extends Model
{
    public const DIRECTION_DEBIT = 'debit';

    public const DIRECTION_CREDIT = 'credit';

    protected $fillable = [
        'invoice_id',
        'adjustment_code',
        'direction',
        'amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSignedAmountAttribute(): float
    {
        $amount = (float) $this->amount;

        return $this->direction === self::DIRECTION_CREDIT ? -$amount : $amount;
    }
}
