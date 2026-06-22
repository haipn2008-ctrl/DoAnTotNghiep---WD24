<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'contract_id',
        'invoice_date',
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
}
