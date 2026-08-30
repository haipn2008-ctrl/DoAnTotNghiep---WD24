<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Services\AdminNotificationService;
use App\Services\ContractHistoryService;
use App\Services\RoomTransferService;
use Illuminate\Http\Request;

class RoomTransferController extends Controller
{
    public function __construct(private readonly RoomTransferService $transfers) {}

    public function index(Request $request)
    {
        $contracts = Contract::query()->with(['room', 'currentMembers'])->managedBy($request->user())
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->get();
        $roomTransfers = RoomTransfer::query()->with(['contract', 'oldRoom', 'newRoom'])
            ->whereHas('contract', fn ($query) => $query->managedBy($request->user()))
            ->latest()->get();
        $rooms = Room::query()->where('status', Room::STATUS_AVAILABLE)
            ->whereDoesntHave('reservingContract')->orderBy('room_code')->get();

        return view('client.contracts.room-transfers.index', compact('contracts', 'roomTransfers', 'rooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'new_room_id' => ['required', 'integer', 'exists:rooms,id'],
            'requested_transfer_date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $contract = Contract::query()->managedBy($request->user())->findOrFail($data['contract_id']);
        $targetRoom = Room::query()->findOrFail($data['new_room_id']);
        $transfer = $this->transfers->requestByTenant($contract, $targetRoom, $request->user(), $data);
        ContractHistoryService::log(
            $contract,
            ContractHistoryService::ROOM_TRANSFER_REQUESTED,
            "Khách thuê đề nghị chuyển sang phòng {$targetRoom->room_code}.",
            $data['reason'],
            ['room_id' => $contract->room_id],
            ['requested_room_id' => $targetRoom->id, 'request_status' => RoomTransfer::STATUS_PENDING],
            $request->user()->id,
        );
        app(AdminNotificationService::class)->roomTransferRequested($transfer);

        return redirect()->route('client.room-transfers.index')
            ->with('success', 'Đã gửi yêu cầu đổi phòng. Ban quản lý sẽ kiểm tra và thông báo kết quả.');
    }
}
