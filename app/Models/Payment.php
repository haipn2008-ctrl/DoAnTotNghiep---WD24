<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa giao dịch thanh toán. Hãy chuyển trạng thái để giữ chứng từ.');
        });
    }

    const METHOD_CASH = 'cash';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_QR = 'qr';

    const STATUS_PENDING = 'pending';

    const STATUS_SUCCESS = 'success';

    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'invoice_id',
        'amount_paid',
        'payment_date',
        'payment_method',
        'transaction_code',
        'status',
        'submitted_by',
        'proof_image',
        'confirmed_by',
        'reviewed_at',
        'review_note',
        'note',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCash()
    {
        return $this->payment_method === self::METHOD_CASH;
    }

    public function isBankTransfer()
    {
        return $this->payment_method === self::METHOD_BANK_TRANSFER;
    }

    public function isQr()
    {
        return $this->payment_method === self::METHOD_QR;
    }
}
