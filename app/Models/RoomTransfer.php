<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTransfer extends Model
{
    public const SOURCE_TENANT = 'tenant';

    public const SOURCE_ADMIN = 'admin';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_APPENDIX = 'pending_appendix';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'contract_id', 'old_room_id', 'new_room_id', 'requested_by', 'source',
        'requested_transfer_date', 'effective_date', 'reason', 'status', 'admin_reason',
        'processed_by', 'processed_at', 'completed_at', 'old_checkout_reading_id',
        'new_handover_reading_id', 'transfer_invoice_id', 'deposit_invoice_id', 'outstanding_amount',
        'old_monthly_rent', 'new_monthly_rent', 'old_deposit_amount',
        'new_deposit_amount', 'deposit_difference', 'remaining_deposit_credit',
        'financial_snapshot', 'execution_payload',
    ];

    protected function casts(): array
    {
        return [
            'requested_transfer_date' => 'date',
            'effective_date' => 'date',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'outstanding_amount' => 'decimal:2',
            'old_monthly_rent' => 'decimal:2',
            'new_monthly_rent' => 'decimal:2',
            'old_deposit_amount' => 'decimal:2',
            'new_deposit_amount' => 'decimal:2',
            'deposit_difference' => 'decimal:2',
            'remaining_deposit_credit' => 'decimal:2',
            'financial_snapshot' => 'array',
            'execution_payload' => 'array',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function oldRoom()
    {
        return $this->belongsTo(Room::class, 'old_room_id');
    }

    public function newRoom()
    {
        return $this->belongsTo(Room::class, 'new_room_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function oldCheckoutReading()
    {
        return $this->belongsTo(UtilityReading::class, 'old_checkout_reading_id');
    }

    public function newHandoverReading()
    {
        return $this->belongsTo(UtilityReading::class, 'new_handover_reading_id');
    }

    public function transferInvoice()
    {
        return $this->belongsTo(Invoice::class, 'transfer_invoice_id');
    }

    public function depositInvoice()
    {
        return $this->belongsTo(Invoice::class, 'deposit_invoice_id');
    }

    public function items()
    {
        return $this->hasMany(RoomTransferItem::class);
    }

    public function appendix()
    {
        return $this->hasOne(ContractAppendix::class);
    }
}
