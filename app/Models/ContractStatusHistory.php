<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractStatusHistory extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Lịch sử trạng thái hợp đồng là dữ liệu bất biến.');
        });

        static::deleting(function (): never {
            throw new \LogicException('Không được xóa lịch sử trạng thái hợp đồng.');
        });
    }

    protected $guarded = [];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
