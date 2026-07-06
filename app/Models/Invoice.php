<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Invoice Type
    |--------------------------------------------------------------------------
    */

    const TYPE_ROOM = 'room';

    const TYPE_UTILITY = 'utility';

    /*
    |--------------------------------------------------------------------------
    | Invoice Status
    |--------------------------------------------------------------------------
    */

    const STATUS_UNPAID = 'unpaid';

    const STATUS_PARTIAL = 'partial';

    const STATUS_PAID = 'paid';

    /**
     * Các trường được phép gán dữ liệu.
     */
    protected $fillable = [

        'contract_id',

        'room_id',

        'utility_reading_id',

        'invoice_code',

        'type',

        'month',

        'year',

        'invoice_date',

        'due_date',

        'total_amount',

        'status',
    ];

    /**
     * Ép kiểu dữ liệu.
     */
    protected $casts = [

        'invoice_date' => 'date',

        'due_date' => 'date',

        'total_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Thuộc hợp đồng.
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Thuộc phòng.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Thuộc kỳ ghi điện nước.
     */
    public function utilityReading()
    {
        return $this->belongsTo(UtilityReading::class);
    }

    /**
     * Danh sách chi tiết hóa đơn.
     */
    public function details()
    {
        return $this->hasMany(InvoiceDetail::class)
            ->orderBy('sort_order');
    }

    /**
     * Danh sách thanh toán.
     */
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
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopePartial($query)
    {
        return $query->where('status', self::STATUS_PARTIAL);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helper
    |--------------------------------------------------------------------------
    */

    public function isUnpaid()
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isPartial()
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    /*
    |--------------------------------------------------------------------------
    | Type Helper
    |--------------------------------------------------------------------------
    */

    public function isRoomInvoice()
    {
        return $this->type === self::TYPE_ROOM;
    }

    public function isUtilityInvoice()
    {
        return $this->type === self::TYPE_UTILITY;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Tổng tiền đã thanh toán.
     */
    public function getPaidAmountAttribute()
    {
        return $this->payments()
            ->success()
            ->sum('amount_paid');
    }

    /**
     * Số tiền còn phải thanh toán.
     */
    public function getRemainingAmountAttribute()
    {
        return max(
            0,
            $this->total_amount - $this->paid_amount
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Business Helper
    |--------------------------------------------------------------------------
    */

    /**
     * Kiểm tra hóa đơn quá hạn.
     */
    public function isOverdue()
    {
        return $this->due_date
            && now()->gt($this->due_date)
            && !$this->isPaid();
    }

    /**
     * Có thể thanh toán.
     */
    public function canPay()
    {
        return !$this->isPaid();
    }

    /**
     * Tính lại tổng tiền.
     */
    public function calculateTotal()
    {
        if ($this->relationLoaded('details')) {

            $this->total_amount = $this->details->sum('amount');
        } else {

            $this->total_amount = $this->details()->sum('amount');
        }

        return $this;
    }

    /**
     * Tính lại tổng tiền và lưu.
     */
    public function refreshTotal()
    {
        $this->calculateTotal();

        $this->save();

        return $this;
    }
}
