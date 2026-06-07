<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_name',
        'amount'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
