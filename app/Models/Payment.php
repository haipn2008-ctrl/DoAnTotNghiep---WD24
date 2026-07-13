<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Payment Method
    |--------------------------------------------------------------------------
    */

    const METHOD_CASH = 'cash';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_QR = 'qr';

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    const STATUS_PENDING = 'pending';

    const STATUS_SUCCESS = 'success';

    const STATUS_FAILED = 'failed';

    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [

        'invoice_id',

        'amount_paid',

        'payment_date',

        'payment_method',

        'transaction_code',

        'status',

        'confirmed_by',

        'note',

    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [

        'payment_date' => 'date',

        'amount_paid' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Thuộc hóa đơn.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Người xác nhận thanh toán.
     */
    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
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

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
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

    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Method Helper
    |--------------------------------------------------------------------------
    */

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
