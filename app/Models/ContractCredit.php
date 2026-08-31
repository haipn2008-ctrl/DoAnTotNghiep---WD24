<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractCredit extends Model
{
    protected $fillable = [
        'contract_id',
        'source_invoice_id',
        'credit_code',
        'amount',
        'remaining_amount',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function sourceInvoice()
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications()
    {
        return $this->hasMany(ContractCreditApplication::class);
    }
}
