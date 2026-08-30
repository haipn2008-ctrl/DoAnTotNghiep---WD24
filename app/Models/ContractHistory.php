<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractHistory extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Lịch sử hợp đồng là dữ liệu bất biến.');
        });

        static::deleting(function (): never {
            throw new \LogicException('Không được xóa lịch sử hợp đồng.');
        });
    }

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
