<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    public const CATEGORY_UTILITY = 'utility';

    public const CATEGORY_ASSET = 'asset';

    protected $fillable = [
        'name',
        'description',
        'category',
        'is_quantifiable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_quantifiable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUtilities($query)
    {
        return $query->where('category', self::CATEGORY_UTILITY);
    }

    public function scopeAssets($query)
    {
        return $query->where('category', self::CATEGORY_ASSET);
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category === self::CATEGORY_UTILITY ? 'Tiện ích' : 'Tài sản';
    }

    public function rooms()
    {
        return $this->belongsToMany(
            Room::class,
            'amenity_room'
        )->withPivot(['quantity', 'condition', 'note'])->withTimestamps();
    }
}
