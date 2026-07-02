<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
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
        'status'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute()
    {
        return (float) $this->payments()->sum('amount_paid');
    }

    public function getEffectiveStatusAttribute()
    {
        $paidAmount = (float) $this->paid_amount;
        $totalAmount = (float) $this->total_amount;

        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public function getBalanceAmountAttribute()
    {
        return max((float) $this->total_amount - $this->paid_amount, 0);
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->effective_status) {
            'paid' => 'Đã thanh toán',
            'partial' => 'Thanh toán một phần',
            'unpaid' => 'Chưa thanh toán',
            default => ucfirst($this->effective_status),
        };
    }
}
