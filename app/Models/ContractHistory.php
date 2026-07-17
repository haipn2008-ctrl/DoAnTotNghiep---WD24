<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractHistory extends Model
{
    protected $fillable = [

        'contract_id',

        'user_id',

        'action',

        'reason',

        'description',

        'old_data',

        'new_data',

    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}