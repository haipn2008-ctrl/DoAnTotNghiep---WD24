<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount_paid',
        'payment_date',
        'note'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
