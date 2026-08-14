<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractExtensionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'current_end_date',
        'requested_end_date',
        'reason',
        'status',
        'admin_note',
        'processed_at',
    ];

    protected $casts = [
        'current_end_date'   => 'date',
        'requested_end_date' => 'date',
        'processed_at'       => 'datetime',
    ];

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}