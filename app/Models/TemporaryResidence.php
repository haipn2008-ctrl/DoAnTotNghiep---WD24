<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TemporaryResidence extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa hồ sơ tạm trú. Hãy hủy hồ sơ để giữ lịch sử.');
        });
    }

    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'contract_tenant_id',
        'start_date',
        'end_date',
        'reference_number',
        'status',
        'note',
        'evidence_path',
        'evidence_original_name',
        'evidence_mime_type',
        'signature',
        'signed_at',
        'verified_by',
        'verified_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'verified_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractTenant()
    {
        return $this->belongsTo(ContractTenant::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => $this->evidence_path ? 'Đã cập nhật minh chứng' : 'Chờ bổ sung minh chứng',
            'pending' => 'Chờ bổ sung minh chứng',
            'expired' => 'Đã hết hiệu lực',
            'cancelled' => 'Đã hủy',
            default => $this->status,
        };
    }

    public function evidenceExists(): bool
    {
        return filled($this->evidence_path)
            && Storage::disk('local')->exists($this->evidence_path);
    }

    public function evidenceIsPdf(): bool
    {
        if (strtolower((string) $this->evidence_mime_type) === 'application/pdf') {
            return true;
        }

        return strtolower(pathinfo(
            $this->evidence_original_name ?: (string) $this->evidence_path,
            PATHINFO_EXTENSION
        )) === 'pdf';
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
