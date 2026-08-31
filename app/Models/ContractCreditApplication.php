<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractCreditApplication extends Model
{
    protected $fillable = ['contract_credit_id', 'invoice_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function credit()
    {
        return $this->belongsTo(ContractCredit::class, 'contract_credit_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
