<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = [
        'name',
        'description',
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

    public function rooms()
    {
        return $this->belongsToMany(
            Room::class,
            'amenity_room'
        )->withPivot(['quantity', 'condition', 'note'])->withTimestamps();
    }
}
