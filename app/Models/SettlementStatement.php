<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementStatement extends Model
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_AWAITING_REFUND = 'awaiting_refund';
    public const STATUS_BALANCED = 'balanced';
    public const STATUS_SETTLED = 'settled';

    protected $guarded = [];

    protected $casts = [
        'final_charge_amount' => 'decimal:2',
        'previous_outstanding_amount' => 'decimal:2',
        'deposit_credit' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function checkoutReading()
    {
        return $this->belongsTo(UtilityReading::class, 'checkout_reading_id');
    }

    public function items()
    {
        return $this->hasMany(SettlementStatementItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
