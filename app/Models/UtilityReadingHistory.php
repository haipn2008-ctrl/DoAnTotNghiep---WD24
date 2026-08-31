<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtilityReadingHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'utility_reading_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'snapshot',
        'previous_snapshot',
        'performed_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'previous_snapshot' => 'array',
        'performed_at' => 'datetime',
    ];

    public function reading()
    {
        return $this->belongsTo(UtilityReading::class, 'utility_reading_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
