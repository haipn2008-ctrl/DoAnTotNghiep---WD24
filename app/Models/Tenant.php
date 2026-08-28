<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa hồ sơ khách thuê. Hãy lưu trữ hồ sơ để giữ lịch sử.');
        });
    }

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'status',
        'archived_at',
        'archived_by',
        'archive_reason',

        'full_name',
        'date_of_birth',
        'gender',

        'cccd',
        'cccd_issue_date',
        'cccd_issue_place',

        'phone',
        'email',

        'address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'cccd_issue_date' => 'date',
        'archived_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function representativeContracts()
    {
        return $this->hasMany(
            Contract::class,
            'representative_tenant_id'
        );
    }

    /**
     * Các hợp đồng mà khách thuê là một thành viên thuê.
     */
    public function memberContracts()
    {
        return $this->belongsToMany(
            Contract::class,
            'contract_tenants'
        )
            ->withPivot([
                'role',
                'status',
                'full_name',
                'actual_move_in_at',
                'actual_move_out_at',
            ])
            ->withTimestamps();
    }

    /**
     * Tư cách thành viên thuê trong hợp đồng.
     */
    public function contractMemberships()
    {
        return $this->hasMany(ContractTenant::class);
    }

    /**
     * Giấy tờ nhận diện / CCCD.
     */
    public function document(): HasOne
    {
        return $this->hasOne(TenantDocument::class);
    }

    /**
     * Xe của khách thuê.
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Thông tin tạm trú của khách thuê.
     */
    public function temporaryResidences()
    {
        return $this->hasMany(TemporaryResidence::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function activeContract()
    {
        return $this->contracts()
            ->whereIn(
                'status',
                Contract::OPEN_OCCUPANCY_STATUSES
            )
            ->first();
    }

    public function isRenting()
    {
        return $this->contracts()
            ->whereIn(
                'status',
                Contract::OPEN_OCCUPANCY_STATUSES
            )
            ->exists();
    }

    public function usesPortal(): bool
    {
        return $this->user_id !== null;
    }

    public function isOffline(): bool
    {
        return ! $this->usesPortal();
    }

    public function scopeEligibleForContract($query)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('full_name')
            ->whereNotNull('date_of_birth')
            ->whereDate('date_of_birth', '<=', now()->subYears(18)->toDateString())
            ->whereNotNull('gender')
            ->whereNotNull('cccd')
            ->whereNotNull('cccd_issue_date')
            ->whereNotNull('cccd_issue_place')
            ->whereNotNull('phone')
            ->whereNotNull('email')
            ->whereNotNull('address')
            ->whereHas('user', fn ($userQuery) => $userQuery->where('status', User::STATUS_ACTIVE));
    }

    public function hasCompleteRentalProfile(): bool
    {
        $this->loadMissing('user');

        return $this->status === self::STATUS_ACTIVE
            && $this->user?->status === User::STATUS_ACTIVE
            && filled($this->full_name)
            && filled($this->date_of_birth)
            && $this->date_of_birth->lte(now()->subYears(18))
            && filled($this->gender)
            && filled($this->cccd)
            && filled($this->cccd_issue_date)
            && filled($this->cccd_issue_place)
            && filled($this->phone)
            && filled($this->email)
            && filled($this->address);
    }
}
