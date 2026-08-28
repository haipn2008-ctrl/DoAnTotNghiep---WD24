<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa phương tiện. Hãy hủy yêu cầu hoặc gỡ phương tiện.');
        });
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REMOVED = 'removed';

    protected $fillable = [
        'tenant_id',
        'vehicle_type',
        'vehicle_name',
        'license_plate',
        'archived_license_plate',
        'color',
        'note',
        'vehicle_image',
        'status',
        'submitted_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'removed_at',
        'removed_by',
        'removal_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Xe thuộc về khách thuê
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function remover()
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function getDisplayLicensePlateAttribute(): ?string
    {
        return $this->license_plate ?: $this->archived_license_plate;
    }
}
