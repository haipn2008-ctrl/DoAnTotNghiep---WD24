<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Ảnh hiện trạng là bằng chứng bất biến.');
        });

        static::deleting(function (): never {
            throw new \LogicException('Không được xóa ảnh hiện trạng đã ghi nhận.');
        });
    }

    public const TYPE_BASELINE = 'baseline';

    public const TYPE_HANDOVER = 'handover';

    public const TYPE_CHECKOUT = 'checkout';

    public const TYPE_MAINTENANCE = 'maintenance';

    public const TYPE_GENERAL = 'general';

    public const TYPE_LEGACY = 'legacy';

    public const UPLOAD_TYPES = [
        self::TYPE_BASELINE,
        self::TYPE_CHECKOUT,
    ];

    protected $fillable = [
        'room_id',
        'contract_id',
        'uploaded_by',
        'evidence_type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'sha256',
        'caption',
        'taken_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
            'metadata' => 'array',
            'file_size' => 'integer',
        ];
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
