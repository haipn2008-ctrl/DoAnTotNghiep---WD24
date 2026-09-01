<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SupportRequest extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new \LogicException('Không được xóa yêu cầu hỗ trợ. Hãy chuyển trạng thái xử lý.');
        });
    }

    public const STATUS_NEW = 'new';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'submission_token',
        'user_id',
        'tenant_id',
        'contract_id',
        'category',
        'subject',
        'description',
        'attachment',
        'status',
        'admin_response',
        'handled_by',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function attachmentExists(): bool
    {
        return filled($this->attachment) && Storage::disk('local')->exists($this->attachment);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}

