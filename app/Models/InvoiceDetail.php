<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    protected $fillable = [
        'invoice_id',
        'type',
        'name',
        'quantity',
        'unit',
        'unit_price',
        'amount',
        'old_index',
        'new_index',
        'note',
        'sort_order',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
