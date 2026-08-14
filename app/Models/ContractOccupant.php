<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractOccupant extends Model
{
    public const ROLE_REPRESENTATIVE = 'representative';
    public const ROLE_OCCUPANT = 'occupant';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_MOVED_OUT = 'moved_out';
    public const STATUS_WITHDRAWN = 'withdrawn';
    public const STATUS_NON_RESIDENT = 'non_resident';

    protected $fillable = [
        'contract_id', 'tenant_id', 'replaces_occupant_id', 'role', 'full_name',
        'date_of_birth', 'identity_number', 'identity_front_path', 'identity_back_path',
        'phone', 'relationship', 'address', 'status',
        'declared_by', 'reviewed_by', 'reviewed_at', 'review_note',
        'actual_move_in_at', 'actual_move_out_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
        'actual_move_in_at' => 'datetime',
        'actual_move_out_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException('Không được xóa hồ sơ người ở. Hãy chuyển trạng thái để giữ lịch sử cư trú.');
        });
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function declarer()
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function histories()
    {
        return $this->hasMany(ContractOccupantHistory::class)->orderBy('performed_at')->orderBy('id');
    }

    public function scopeCurrent($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_CHECKED_IN]);
    }

    public function scopeApprovedForMoveIn($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_CHECKED_IN]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt, chờ nhận phòng',
            self::STATUS_REJECTED => 'Đã từ chối',
            self::STATUS_CHECKED_IN => 'Đang ở',
            self::STATUS_MOVED_OUT => 'Đã rời phòng',
            self::STATUS_WITHDRAWN => 'Đã rút khai báo',
            self::STATUS_NON_RESIDENT => 'Người thuê không cư trú',
            default => $this->status,
        };
    }
}
