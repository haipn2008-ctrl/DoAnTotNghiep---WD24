<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    public const CATEGORY_ELECTRICITY = 'electricity';
    public const CATEGORY_WATER = 'water';
    public const CATEGORY_INTERNET = 'internet';
    public const CATEGORY_MAINTENANCE = 'maintenance';
    public const CATEGORY_CLEANING = 'cleaning';
    public const CATEGORY_ASSET = 'asset';
    public const CATEGORY_OTHER = 'other';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CASH = 'cash';

    protected $fillable = [
        'expense_code',
        'category',
        'title',
        'amount',
        'expense_date',
        'room_id',
        'support_request_id',
        'payer_name',
        'payment_method',
        'receipt_image',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public static function categories(): array
    {
        return [
            self::CATEGORY_ELECTRICITY => 'Tiền điện nộp nhà nước',
            self::CATEGORY_WATER => 'Tiền nước nộp nhà nước',
            self::CATEGORY_INTERNET => 'Cước Internet / Wifi',
            self::CATEGORY_MAINTENANCE => 'Sửa chữa & Bảo trì đồ hỏng',
            self::CATEGORY_CLEANING => 'Vệ sinh & Rác',
            self::CATEGORY_ASSET => 'Mua sắm trang thiết bị',
            self::CATEGORY_OTHER => 'Chi phí khác',
        ];
    }

    public static function categoryBadges(): array
    {
        return [
            self::CATEGORY_ELECTRICITY => ['label' => 'Điện nhà nước', 'class' => 'bg-amber-50 text-amber-700 ring-amber-200'],
            self::CATEGORY_WATER => ['label' => 'Nước nhà nước', 'class' => 'bg-cyan-50 text-cyan-700 ring-cyan-200'],
            self::CATEGORY_INTERNET => ['label' => 'Internet', 'class' => 'bg-blue-50 text-blue-700 ring-blue-200'],
            self::CATEGORY_MAINTENANCE => ['label' => 'Sửa chữa / Bảo trì', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
            self::CATEGORY_CLEANING => ['label' => 'Vệ sinh / Rác', 'class' => 'bg-teal-50 text-teal-700 ring-teal-200'],
            self::CATEGORY_ASSET => ['label' => 'Mua sắm thiết bị', 'class' => 'bg-purple-50 text-purple-700 ring-purple-200'],
            self::CATEGORY_OTHER => ['label' => 'Chi phí khác', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_BANK_TRANSFER => 'Chuyển khoản',
            self::METHOD_CASH => 'Tiền mặt',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? 'Chi phí khác';
    }

    public function getCategoryBadgeAttribute(): array
    {
        return self::categoryBadges()[$this->category] ?? ['label' => 'Chi phí khác', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'];
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethods()[$this->payment_method] ?? 'Chuyển khoản';
    }

    public function receiptExists(): bool
    {
        return filled($this->receipt_image) && Storage::disk('local')->exists($this->receipt_image);
    }

    public static function generateExpenseCode(?string $date = null): string
    {
        $dateObj = $date ? \Carbon\Carbon::parse($date) : now();
        $prefix = 'EXP-' . $dateObj->format('Ym') . '-';

        $latest = self::where('expense_code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('expense_code');

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $nextSeq = (int)$matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }
}

