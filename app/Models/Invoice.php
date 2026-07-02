<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [

        'contract_id',

        'invoice_code',

        'type',

        'month',

        'year',

        'invoice_date',

        'due_date',

        'total_amount',

        'status',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Hợp đồng
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // Chi tiết hóa đơn
    public function details()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    // Thanh toán
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
