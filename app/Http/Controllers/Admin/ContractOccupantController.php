<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractOccupant;
use App\Services\ContractOccupantService;
use Illuminate\Http\Request;

class ContractOccupantController extends Controller
{
    public function __construct(private readonly ContractOccupantService $occupants) {}

    public function approve(Request $request, ContractOccupant $occupant)
    {
        $this->occupants->approve($occupant, $request->user());

        return back()->with('success', 'Đã duyệt người ở.');
    }

    public function reject(Request $request, ContractOccupant $occupant)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->occupants->reject($occupant, $request->user(), $data['reason']);

        return back()->with('success', 'Đã từ chối khai báo người ở.');
    }

    public function moveOut(Request $request, ContractOccupant $occupant)
    {
        $data = $request->validate([
            'actual_move_out_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->occupants->moveOut($occupant, $request->user(), $data['actual_move_out_at'], $data['reason']);

        return back()->with('success', 'Đã ghi nhận người ở rời phòng và cập nhật số người hiện tại.');
    }
}
