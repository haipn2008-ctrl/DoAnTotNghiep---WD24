<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    protected $fillable = [

        'invoice_id',

        'service_type',

        'service_name',

        'quantity',

        'unit_price',

        'amount',

        'utility_reading_id',

        'note',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function utilityReading()
    {
        return $this->belongsTo(UtilityReading::class);
    }
}
