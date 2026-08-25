<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettlementStatementItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function statement()
    {
        return $this->belongsTo(SettlementStatement::class, 'settlement_statement_id');
    }
}
