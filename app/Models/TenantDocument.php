<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
