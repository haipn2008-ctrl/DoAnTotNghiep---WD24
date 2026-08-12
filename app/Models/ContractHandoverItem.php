<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractHandoverItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_quantifiable' => 'boolean',
            'quantity' => 'integer',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }
}
