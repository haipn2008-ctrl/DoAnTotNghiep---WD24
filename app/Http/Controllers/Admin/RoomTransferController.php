<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractHistoryService;
use App\Services\RoomTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RoomTransferController extends Controller
{
    public function __construct(private readonly RoomTransferService $transfers) {}

    public function index()
    {
        $roomTransfers = RoomTransfer::query()->with([
            'contract.tenant', 'oldRoom', 'newRoom', 'requester', 'processor', 'transferInvoice', 'depositInvoice', 'appendix',
        ])->latest()->get();

        return view('admin.contracts.room-transfers.index', compact('roomTransfers'));
    }

    public function create(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless(in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true), 409);

        $selectedRoomId = $request->integer('room_id') ?: null;

        return view('admin.contracts.room-transfers.form', $this->formData($contract, $selectedRoomId));
    }

    public function review(RoomTransfer $roomTransfer)
    {
        Gate::authorize('manageLifecycle', $roomTransfer->contract);
        abort_unless($roomTransfer->status === RoomTransfer::STATUS_PENDING, 409);

        return view('admin.contracts.room-transfers.form', [
            ...$this->formData($roomTransfer->contract, $roomTransfer->new_room_id),
            'roomTransfer' => $roomTransfer->load(['oldRoom', 'newRoom', 'requester']),
        ]);
    }

    public function store(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $this->validatedExecution($request);
        $targetRoom = Room::query()->findOrFail($data['new_room_id']);
        $data['new_assets'] = $data['new_assets'][$targetRoom->id] ?? [];
        $transfer = $this->transfers->createWithAppendix($contract, $targetRoom, $request->user(), $data);
        app(ClientNotificationService::class)->appendix(
            $transfer->appendix,
            'Có phụ lục chuyển phòng cần xác nhận',
            'Ban quản lý đã lập phụ lục chuyển phòng '.$transfer->appendix->code.'. Vui lòng kiểm tra và phản hồi.'
        );

        return redirect()->route('admin.contract-appendices.show', $transfer->appendix)
            ->with('success', 'Đã gửi phụ lục chuyển phòng cho khách xác nhận. Hợp đồng chưa thay đổi.');
    }

    public function approve(Request $request, RoomTransfer $roomTransfer)
    {
        Gate::authorize('manageLifecycle', $roomTransfer->contract);
        $data = $this->validatedExecution($request, $roomTransfer->new_room_id);
        $data['new_assets'] = $data['new_assets'][$roomTransfer->new_room_id] ?? [];
        $transfer = $this->transfers->approveWithAppendix($roomTransfer, $request->user(), $data);
        app(ClientNotificationService::class)->appendix(
            $transfer->appendix,
            'Có phụ lục chuyển phòng cần xác nhận',
            'Yêu cầu đổi phòng đã được duyệt về nguyên tắc. Vui lòng kiểm tra phụ lục '.$transfer->appendix->code.'.'
        );

        return redirect()->route('admin.contract-appendices.show', $transfer->appendix)
            ->with('success', 'Đã duyệt về nguyên tắc và gửi phụ lục cho khách. Chưa thực hiện chuyển phòng.');
    }

    public function reject(Request $request, RoomTransfer $roomTransfer)
    {
        Gate::authorize('manageLifecycle', $roomTransfer->contract);
        $data = $request->validate(['admin_reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $transfer = $this->transfers->reject($roomTransfer, $request->user(), $data['admin_reason']);
        app(AdminNotificationService::class)->resolve('room_transfer_request', $transfer);
        ContractHistoryService::log(
            $transfer->contract,
            ContractHistoryService::ROOM_TRANSFER_REJECTED,
            'Ban quản lý đã từ chối yêu cầu đổi phòng.',
            $data['admin_reason'],
            ['request_status' => RoomTransfer::STATUS_PENDING],
            ['request_status' => RoomTransfer::STATUS_REJECTED],
            $request->user()->id,
        );
        app(ClientNotificationService::class)->contract(
            $transfer->contract,
            'room_transfer_rejected',
            'Yêu cầu đổi phòng chưa được duyệt',
            "Yêu cầu chuyển từ phòng {$transfer->oldRoom->room_code} sang {$transfer->newRoom->room_code} đã bị từ chối. Lý do: {$data['admin_reason']}"
        );

        return redirect()->route('admin.room-transfers.index')->with('success', 'Đã từ chối yêu cầu và thông báo cho khách.');
    }

    private function validatedExecution(Request $request, ?int $fixedRoomId = null): array
    {
        if ($fixedRoomId) {
            $request->merge(['new_room_id' => $fixedRoomId]);
        }

        return $request->validate([
            'new_room_id' => ['required', 'integer', Rule::exists('rooms', 'id')],
            'effective_date' => ['required', 'date'],
            'admin_reason' => ['required', 'string', 'min:3', 'max:2000'],
            'old_electricity' => ['required', 'integer', 'min:0'],
            'old_water' => ['required', 'integer', 'min:0'],
            'new_electricity' => ['required', 'integer', 'min:0'],
            'new_water' => ['required', 'integer', 'min:0'],
            'old_assets' => ['nullable', 'array'],
            'old_assets.*.quantity' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'old_assets.*.condition' => ['nullable', Rule::in(['normal', 'damaged'])],
            'old_assets.*.note' => ['nullable', 'string', 'max:1000'],
            'new_assets' => ['nullable', 'array'],
            'new_assets.*' => ['nullable', 'array'],
            'new_assets.*.*.quantity' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'new_assets.*.*.condition' => ['nullable', Rule::in(['normal', 'damaged'])],
            'new_assets.*.*.note' => ['nullable', 'string', 'max:1000'],
            'confirm_transfer' => ['required', 'accepted'],
        ], [
            'confirm_transfer.required' => 'Bạn phải xác nhận đã đối chiếu công nợ, chỉ số và tài sản.',
            'confirm_transfer.accepted' => 'Bạn phải xác nhận đã đối chiếu công nợ, chỉ số và tài sản.',
        ]);
    }

    private function formData(Contract $contract, ?int $selectedRoomId = null): array
    {
        $contract->load(['room', 'tenant', 'currentMembers', 'handoverItems']);
        $rooms = Room::query()->with('amenities')
            ->where(fn ($query) => $query->where('status', Room::STATUS_AVAILABLE)
                ->when($selectedRoomId, fn ($query) => $query->orWhere('id', $selectedRoomId)))
            ->whereKeyNot($contract->room_id)->orderBy('room_code')->get();
        $lastOldReading = $contract->utilityReadings()->where('room_id', $contract->room_id)
            ->latest('record_date')->latest('id')->first();
        $roomMeters = $rooms->mapWithKeys(function (Room $room): array {
            $reading = $room->utilityReadings()->latest('record_date')->latest('id')->first();

            return [$room->id => [
                'electricity' => (int) ($reading?->electricity_new ?? 0),
                'water' => (int) ($reading?->water_new ?? 0),
            ]];
        });
        $outstanding = $contract->invoices()->whereIn('status', ['unpaid', 'partial'])
            ->with(['payments', 'adjustments'])->get()->sum(fn ($invoice) => $invoice->remaining_amount);

        return compact('contract', 'rooms', 'lastOldReading', 'roomMeters', 'outstanding', 'selectedRoomId');
    }

    private function notifyCompleted(RoomTransfer $transfer, bool $adminInitiated): void
    {
        $message = $adminInitiated
            ? "Ban quản lý đã chuyển hợp đồng {$transfer->contract->contract_code} từ phòng {$transfer->oldRoom->room_code} sang {$transfer->newRoom->room_code}. Lý do: {$transfer->admin_reason}. Hồ sơ tạm trú phòng cũ đã hết hiệu lực và cần cập nhật lại cho phòng mới."
            : "Yêu cầu đổi phòng của bạn đã được duyệt. Hợp đồng {$transfer->contract->contract_code} đã chuyển từ phòng {$transfer->oldRoom->room_code} sang {$transfer->newRoom->room_code}. Ghi chú của ban quản lý: {$transfer->admin_reason}. Hồ sơ tạm trú phòng cũ đã hết hiệu lực và cần cập nhật lại cho phòng mới.";
        app(ClientNotificationService::class)->contract(
            $transfer->contract,
            'room_transfer_completed',
            'Đã hoàn tất đổi phòng',
            $message,
        );
    }
}
