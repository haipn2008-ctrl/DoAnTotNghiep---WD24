<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    protected $fillable = [
        'effective_from',
        'electric_price',
        'water_price',
        'internet_fee',
        'service_fee',
    ];

    protected $casts = [
        'electric_price' => 'decimal:2',
        'water_price' => 'decimal:2',
        'internet_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
    ];

    protected function effectiveFrom(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Carbon::parse($value),
            set: fn (DateTimeInterface|string $value) => Carbon::parse($value)->toDateString(),
        );
    }

    public static function forPeriod(DateTimeInterface|string $period, bool $lockForUpdate = false): ?self
    {
        $periodStart = Carbon::parse($period)->startOfMonth();
        $query = self::query()
            ->whereDate('effective_from', '<=', $periodStart)
            ->latest('effective_from')
            ->latest('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
