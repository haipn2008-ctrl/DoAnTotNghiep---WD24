<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ContractAppendix extends Model
{
    public const PRICE_TITLE_FIELDS = [
        'Điều chỉnh giá điện' => ['electric_price'],
        'Điều chỉnh giá nước' => ['water_price'],
        'Điều chỉnh phí Internet' => ['internet_fee'],
        'Điều chỉnh phí dịch vụ chung' => ['service_fee'],
        'Điều chỉnh nhiều đơn giá dịch vụ' => [
            'electric_price', 'water_price', 'internet_fee', 'service_fee',
        ],
    ];

    public const PRICE_FIELD_LABELS = [
        'electric_price' => 'Giá điện',
        'water_price' => 'Giá nước',
        'internet_fee' => 'Phí Internet',
        'service_fee' => 'Phí dịch vụ chung',
    ];

    public const PRICE_FIELD_UNITS = [
        'electric_price' => 'đ/kWh',
        'water_price' => 'đ/m³',
        'internet_fee' => 'đ/người/tháng',
        'service_fee' => 'đ/người/tháng',
    ];

    protected $table = 'contract_appendices';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_TENANT = 'pending_tenant';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_PENDING_SIGNATURE = 'pending_signature';

    public const TYPE_GENERAL = 'general';

    public const TYPE_EXTENSION = 'extension';

    protected $fillable = [
        'contract_id', 'extension_request_id', 'parent_appendix_id', 'appendix_number', 'revision', 'code', 'appendix_type',
        'title', 'legal_basis', 'content', 'price_adjustments', 'effective_from', 'status', 'created_by',
        'sent_at', 'sent_by', 'responded_at', 'responded_by', 'accepted_at',
        'rejected_at', 'rejection_reason', 'content_sha256',
        'signed_evidence_paths', 'signed_evidence_uploaded_at', 'signed_evidence_uploaded_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'price_adjustments' => 'array',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'signed_evidence_paths' => 'array',
        'signed_evidence_uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (ContractAppendix $appendix): void {
            if ($appendix->getOriginal('sent_at') && $appendix->isDirty([
                'contract_id', 'parent_appendix_id', 'appendix_number', 'revision',
                'code', 'title', 'legal_basis', 'content', 'price_adjustments', 'effective_from',
                'sent_at', 'sent_by', 'content_sha256',
            ])) {
                throw new LogicException('Phụ lục đã gửi cho khách không thể sửa trực tiếp.');
            }
        });

        static::deleting(function (ContractAppendix $appendix): void {
            if ($appendix->sent_at) {
                throw new LogicException('Không được xóa phụ lục đã gửi cho khách.');
            }
        });
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_appendix_id');
    }

    public function extensionRequest()
    {
        return $this->belongsTo(ContractExtensionRequest::class, 'extension_request_id');
    }

    public function evidenceUploader()
    {
        return $this->belongsTo(User::class, 'signed_evidence_uploaded_by');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'parent_appendix_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function hasValidContentHash(): bool
    {
        return filled($this->content_sha256)
            && hash_equals($this->content_sha256, hash('sha256', $this->hashPayload()));
    }

    public function hashPayload(): string
    {
        $parts = [
            $this->code,
            $this->title,
            $this->legal_basis ?? '',
            $this->effective_from?->toDateString() ?? '',
            $this->content,
        ];

        if (filled($this->price_adjustments)) {
            $parts[] = json_encode($this->price_adjustments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return implode("\n", $parts);
    }

    public static function priceFieldsForTitle(string $title): array
    {
        return self::PRICE_TITLE_FIELDS[$title] ?? [];
    }

    public function isPriceAdjustment(): bool
    {
        return filled($this->price_adjustments);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PENDING_TENANT => 'Chờ khách xác nhận',
            self::STATUS_ACCEPTED => 'Đã chấp nhận',
            self::STATUS_REJECTED => 'Khách từ chối',
            self::STATUS_SUPERSEDED => 'Đã có bản sửa đổi',
            self::STATUS_PENDING_SIGNATURE => 'Chờ ký và tải minh chứng',
            default => 'Không xác định',
        };
    }

    public function isExtension(): bool
    {
        return $this->appendix_type === self::TYPE_EXTENSION;
    }
}
