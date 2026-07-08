<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityReading extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [

        'contract_id',
        'room_id',

        'month',
        'year',
        'record_date',

        'electric_old',
        'electric_new',
        'electric_unit_price',
        'electricity_image',

        'water_old',
        'water_new',
        'water_unit_price',
        'water_image',

        'status',
        'note',

        'recorded_by',
    ];

    protected $casts = [

        'record_date' => 'date',

        'electric_unit_price' => 'decimal:2',
        'water_unit_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function invoice()
    {
        return $this->hasOne(
            Invoice::class,
            'utility_reading_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeMonth($query, $month, $year)
    {
        return $query
            ->where('month', $month)
            ->where('year', $year);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getElectricUsageAttribute()
    {
        return max(
            0,
            $this->electric_new - $this->electric_old
        );
    }

    public function getWaterUsageAttribute()
    {
        return max(
            0,
            $this->water_new - $this->water_old
        );
    }

    public function getElectricAmountAttribute()
    {
        return round(
            $this->electricUsage * $this->electric_unit_price,
            2
        );
    }

    public function getWaterAmountAttribute()
    {
        return round(
            $this->waterUsage * $this->water_unit_price,
            2
        );
    }

    public function getTotalUtilityAmountAttribute()
    {
        return round(
            $this->electricAmount + $this->waterAmount,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function canGenerateInvoice()
    {
        return $this->isConfirmed() && !$this->invoice;
    }
}
