<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class InvoiceReminder extends Model
{
    public const CHANNEL_SYSTEM = 'system';

    public const CHANNEL_PHONE = 'phone';

    public const CHANNEL_ZALO = 'zalo';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_IN_PERSON = 'in_person';

    public const CHANNEL_OTHER = 'other';

    protected $fillable = [
        'invoice_id',
        'channel',
        'note',
        'reminded_by',
        'reminded_by_name',
        'reminder_date',
        'reminded_at',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'reminded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Lịch sử nhắc thanh toán không thể chỉnh sửa.');
        });

        static::deleting(function (): never {
            throw new LogicException('Lịch sử nhắc thanh toán không thể xóa.');
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function remindedBy()
    {
        return $this->belongsTo(User::class, 'reminded_by');
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            self::CHANNEL_SYSTEM => 'Thông báo trong hệ thống',
            self::CHANNEL_PHONE => 'Điện thoại',
            self::CHANNEL_ZALO => 'Zalo',
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_IN_PERSON => 'Gặp trực tiếp',
            default => 'Kênh khác',
        };
    }
}
