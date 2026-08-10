<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractTerminationRequest extends Model
{
    // ==============================
    // STATUS
    // ==============================

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'contract_id',
        'tenant_id',
        'requested_end_date',
        'reason',
        'status',
        'admin_note',
        'processed_at',
    ];

    protected $casts = [
        'requested_end_date' => 'date',
        'processed_at' => 'datetime',
    ];

    /**
     * Hợp đồng
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Khách thuê
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}