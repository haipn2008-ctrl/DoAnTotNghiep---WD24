<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractTenant extends Model
{
    public const ROLE_REPRESENTATIVE = 'representative';

    public const ROLE_TENANT = 'tenant';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_MOVED_OUT = 'moved_out';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'contract_id', 'tenant_id', 'replaces_contract_tenant_id', 'role', 'full_name',
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
            throw new LogicException('Không được xóa hồ sơ người thuê. Hãy chuyển trạng thái để giữ lịch sử thuê.');
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
        return $this->hasMany(ContractTenantHistory::class)->orderBy('performed_at')->orderBy('id');
    }

    public function temporaryResidences()
    {
        return $this->hasMany(TemporaryResidence::class);
    }

    public function activeTemporaryResidence()
    {
        return $this->hasOne(TemporaryResidence::class)
            ->ofMany(['id' => 'max'], fn ($query) => $query->whereIn('status', ['pending', 'active']));
    }

    public function latestTemporaryResidence()
    {
        return $this->hasOne(TemporaryResidence::class)->latestOfMany();
    }

    public function scopeCurrent($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_CHECKED_IN]);
    }

    public function scopeApprovedForMoveIn($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_CHECKED_IN]);
    }

    public function missingMoveInProfileFields(): array
    {
        $this->loadMissing('tenant');

        $fields = [
            'full_name' => ['value' => $this->full_name, 'label' => 'Họ và tên'],
            'date_of_birth' => ['value' => $this->date_of_birth, 'label' => 'Ngày sinh'],
            'gender' => ['value' => $this->tenant?->gender, 'label' => 'Giới tính'],
            'identity_number' => ['value' => $this->identity_number, 'label' => 'Số CCCD'],
            'cccd_issue_date' => ['value' => $this->tenant?->cccd_issue_date, 'label' => 'Ngày cấp CCCD'],
            'cccd_issue_place' => ['value' => $this->tenant?->cccd_issue_place, 'label' => 'Nơi cấp CCCD'],
            'phone' => ['value' => $this->phone, 'label' => 'Số điện thoại'],
            'address' => ['value' => $this->address, 'label' => 'Địa chỉ thường trú'],
        ];
        if ($this->role !== self::ROLE_REPRESENTATIVE) {
            $fields += [
                'identity_front' => ['value' => $this->identity_front_path, 'label' => 'Ảnh CCCD mặt trước'],
                'identity_back' => ['value' => $this->identity_back_path, 'label' => 'Ảnh CCCD mặt sau'],
            ];
        }

        return collect($fields)
            ->filter(fn (array $field): bool => blank($field['value']))
            ->mapWithKeys(fn (array $field, string $key): array => [$key => $field['label']])
            ->all();
    }

    public function hasCompleteMoveInProfile(): bool
    {
        return $this->missingMoveInProfileFields() === [];
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
            default => $this->status,
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role === self::ROLE_REPRESENTATIVE
            ? 'Người thuê đại diện'
            : 'Người thuê';
    }
}
