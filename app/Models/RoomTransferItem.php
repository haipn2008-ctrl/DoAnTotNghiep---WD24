<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomTransferItem extends Model
{
    public const PHASE_OLD_CHECKOUT = 'old_checkout';

    public const PHASE_NEW_HANDOVER = 'new_handover';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_quantifiable' => 'boolean',
            'quantity' => 'integer',
        ];
    }

    public function transfer()
    {
        return $this->belongsTo(RoomTransfer::class, 'room_transfer_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function amenity()
    {
        return $this->belongsTo(Amenity::class);
    }
}
