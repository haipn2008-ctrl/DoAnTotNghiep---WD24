<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePaymentDelayRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'invoice_id',
        'requested_by',
        'reason',
        'promised_payment_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'approved_until',
    ];

    protected $casts = [
        'promised_payment_date' => 'date',
        'reviewed_at' => 'datetime',
        'approved_until' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
