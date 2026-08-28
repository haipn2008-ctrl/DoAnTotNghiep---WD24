<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TenantDocument extends Model
{
    protected $fillable = [
        'tenant_id',
        'cccd',
        'cccd_issue_date',
        'cccd_issue_place',
        'cccd_front_image',
        'cccd_back_image',
    ];

    protected $casts = [
        'cccd_issue_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Giấy tờ thuộc về khách thuê
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class
        );
    }

    public function imagePath(string $side): ?string
    {
        return match ($side) {
            'front' => $this->cccd_front_image,
            'back' => $this->cccd_back_image,
            default => null,
        };
    }

    public function hasImage(string $side): bool
    {
        $path = $this->imagePath($side);

        return filled($path) && Storage::disk('local')->exists($path);
    }

    public function hasCompleteImagePair(): bool
    {
        return $this->hasImage('front') && $this->hasImage('back');
    }
}
