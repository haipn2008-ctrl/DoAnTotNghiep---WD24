<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function show(Request $request): View
    {
        $contract = $request->user()->tenant?->contracts()
            ->with(['room.amenities'])
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->latest('start_date')
            ->first();

        return view('client.room.show', [
            'contract' => $contract,
            'room' => $contract?->room,
        ]);
    }
}
