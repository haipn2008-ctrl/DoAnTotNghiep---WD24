<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    public const STATUS_MATCHED = 'matched';
    public const STATUS_UNMATCHED = 'unmatched';

    protected $fillable = [
        'provider_transaction_id', 'invoice_id', 'payment_id', 'amount', 'content',
        'transaction_at', 'status', 'message', 'payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_at' => 'datetime',
        'payload' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
