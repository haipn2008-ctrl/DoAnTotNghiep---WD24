<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\ContractHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



class ContractController extends Controller
{
    /**
     * Hiển thị danh sách hợp đồng
     */
    public function index(Request $request)
    {
        $query = Contract::with(['room', 'tenant']);

        if ($request->filled('keyword')) {

            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {

                if (strtoupper(substr($keyword, 0, 2)) == 'HD') {

                    $id = (int) substr($keyword, 2);

                    $q->orWhere('id', $id);
                }

                $q->orWhere('id', $keyword)

                    ->orWhereHas('tenant', function ($tenant) use ($keyword) {

                        $tenant->where(
                            'full_name',
                            'like',
                            "%{$keyword}%"
                        );

                    })

                    ->orWhereHas('room', function ($room) use ($keyword) {

                        $room->where(
                            'room_code',
                            'like',
                            "%{$keyword}%"
                        );

                    });

            });

        }

        if ($request->filled('status')) {

            $query->where('status', $request->status);

        }

        $contracts = $query
            ->latest()
            ->paginate(10);

        $rooms = Room::select(
            'id',
            'room_code',
            'price',
            'status'
        )->get();
        

        $tenants = Tenant::select(
            'id',
            'full_name as name'
        )->get();

        $templates = [];

        return view(
            'admin.contracts.index',
            compact(
                'contracts',
                'rooms',
                'tenants',
                'templates'
            )
        );
    }
    /**
     * Form tạo hợp đồng
     */
    public function create()
    {
        // chỉ lấy phòng đang trống
        $rooms = Room::where('status', 'available')
        ->select('id', 'room_code', 'price')
        ->get();

        // lấy danh sách người thuê
        $tenants = Tenant::select('id', 'full_name as name')->get();

        return redirect()
        ->route('admin.contracts.index');
    }
    /**
     * Lưu hợp đồng
     */
    public function store(Request $request)
    {
        $request->validate([

            'room_id'=>'required|exists:rooms,id',

            'tenant_id'=>'required|exists:tenants,id',

            'start_date'=>'required|date',

            'end_date'=>'required|date|after:start_date',

            'deposit_amount'=>'nullable|numeric|min:0',

            'note'=>'nullable|string',

            'contract_content'=>'nullable|string',

        ]);

        $room = Room::findOrFail($request->room_id);

        $exists = Contract::where('room_id', $request->room_id)
            ->whereIn('status', [
                Contract::STATUS_DRAFT,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_SIGNED,
                Contract::STATUS_DEPOSIT_PAID,
                Contract::STATUS_ACTIVE,
            ])
            ->exists();

        if ($exists) {

            return back()
                ->withInput()
                ->with('error', 'Phòng này đang có hợp đồng.');

        }

        $lastId = (Contract::max('id') ?? 0) + 1;

        $contract = Contract::create([
            'contract_code' => 'HD' . str_pad($lastId, 3, '0', STR_PAD_LEFT),
            'room_id' => $request->room_id,
            'tenant_id' => $request->tenant_id,
            'representative_tenant_id' => $request->tenant_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'monthly_rent' => $room->price,
            'deposit_amount' => $request->deposit_amount,
            'status' => Contract::STATUS_DRAFT,
            'deposit_status' => Contract::DEPOSIT_PENDING,
        ]);

        $room->update([
            'status' => Room::STATUS_OCCUPIED,
            'current_people' => 1,
        ]);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Đang chờ khách thuê ký hợp đồng.');
    }
    /**
     * Chi tiết hợp đồng
     */
    // public function show(Contract $contract)
    // {
    //     return view(
    //         'admin.contracts.show',
    //         compact('contract')
    //     );
    // }
    public function modal(Contract $contract)
    {
        return view(
            'admin.contracts.modal.detail',
            compact('contract')
        );
    }
    /**
     * Form sửa hợp đồng
     */
    public function edit(Contract $contract)
    {
        if (!$contract->canEdit()) {

            return redirect()
                ->route('admin.contracts.show', $contract)
                ->with(
                    'error',
                    'Hợp đồng đã kết thúc nên không thể chỉnh sửa.'
                );

        }

        return redirect()
        ->route('admin.contracts.index')
        ->with(
            'warning',
            'Vui lòng chỉnh sửa hợp đồng bằng cửa sổ (Modal).'
        );
    }

    // Cập nhật thông tin người thuê
    public function update(Request $request, Contract $contract)
    {
        // Không cho phép sửa
        if (!$contract->canEdit()) {

            return redirect()
                ->route('admin.contracts.show', $contract)
                ->with('error', 'Hợp đồng này không được phép chỉnh sửa.');

        }

        $request->validate([

            'monthly_rent'   => 'required|numeric|min:0',

            'deposit_amount' => 'required|numeric|min:0',

            'start_date'     => 'required|date',

            'end_date'       => 'required|date|after:start_date',

            'note'           => 'nullable',

            'reason'         => 'required|string|max:255',

        ]);

        // Dữ liệu cũ
        $oldData = [

            'monthly_rent'   => $contract->monthly_rent,

            'deposit_amount' => $contract->deposit_amount,

            'start_date'     => optional($contract->start_date)->format('Y-m-d'),

            'end_date'       => optional($contract->end_date)->format('Y-m-d'),

            'note'           => $contract->note,

        ];

        // Dữ liệu mới
        $newData = [

            'monthly_rent'   => $request->monthly_rent,

            'deposit_amount' => $request->deposit_amount,

            'start_date'     => $request->start_date,

            'end_date'       => $request->end_date,

            'note'           => $request->note,

        ];

        // Chỉ lấy những trường thay đổi
        $oldChanged = [];

        $newChanged = [];

        foreach ($newData as $key => $value) {

            if (($oldData[$key] ?? null) != $value) {

                $oldChanged[$key] = $oldData[$key] ?? null;

                $newChanged[$key] = $value;

            }

        }

        if (empty($oldChanged)) {

            return back()->with(
                'warning',
                'Không có dữ liệu nào được thay đổi.'
            );

        }

        DB::transaction(function () use (
            $contract,
            $newData,
            $oldChanged,
            $newChanged,
            $request
        ) {

            // Cập nhật hợp đồng
            $contract->update($newData);

            // Lưu lịch sử
            ContractHistory::create([

                'contract_id' => $contract->id,

                'user_id' => Auth::id(),

                'action' => 'edit',

                'reason' => $request->reason,

                'description' => 'Admin chỉnh sửa hợp đồng',

                'old_data' => $oldChanged,

                'new_data' => $newChanged,

            ]);

        });

        return redirect()
            ->route('admin.contracts.show', $contract)
            ->with('success', 'Cập nhật hợp đồng thành công.');
    }

    public function end(Request $request, Contract $contract)
    {
        if (!$contract->canTerminate()) {

            return back()->with(
                'error',
                'Hợp đồng này không thể kết thúc.'
            );

        }

        $request->validate([

            'actual_end_date' => [

                'required',

                'date',

                'after_or_equal:' . $contract->start_date->format('Y-m-d'),

            ],

            'termination_reason' => 'required|string|max:255',

            'termination_note' => 'nullable|string',

        ]);
        $oldStatus = $contract->status;

        $oldRoomStatus = $contract->room->status;

        DB::transaction(function () use (

                $contract,

                $request,

                $oldStatus,

                $oldRoomStatus

            ) {

            // Cập nhật hợp đồng
            $contract->update([

                'status' => Contract::STATUS_TERMINATED,

                'terminated_at' => now(),

                'terminated_by' => Auth::id(),

                'actual_end_date' => $request->actual_end_date,

                'termination_reason' => $request->termination_reason,

                'termination_note' => $request->termination_note,

            ]);

            // Trả phòng
            $contract->room->update([

                'status' => Room::STATUS_AVAILABLE,

                'current_people' => 0,

            ]);

            // Lưu lịch sử
            ContractHistory::create([

                'contract_id' => $contract->id,

                'user_id' => Auth::id(),

                'action' => 'terminate',

                'reason' => $request->termination_reason,

                'description' => 'Kết thúc hợp đồng',

                'old_data' => [

                    'status' => $oldStatus,

                    'room_status' => $oldRoomStatus,

                    'end_date' => optional($contract->end_date)->format('Y-m-d'),

                ],

                'new_data' => [

                    'status' => Contract::STATUS_TERMINATED,

                    'room_status' => Room::STATUS_AVAILABLE,

                    'actual_end_date' => $request->actual_end_date,

                ],

            ]);

        });

        return redirect()

            ->route('admin.contracts.index')

            ->with(

                'success',

                'Kết thúc hợp đồng thành công.'

            );
    }
    
    public function extend(Request $request, Contract $contract)
    {
        if (!$contract->canExtend()) {

            return back()->with(
                'error',
                'Hợp đồng này không thể gia hạn.'
            );
        }

        $request->validate([

            'new_end_date' => [
                'required',
                'date',
                'after:' . $contract->end_date->format('Y-m-d'),
            ],

            'extend_reason' => 'required|string|max:255',

            'extend_note' => 'nullable|string',

        ]);

        DB::transaction(function () use ($contract, $request) {

            $oldEndDate = $contract->end_date;

            $contract->update([

                'extended_at' => now(),

                'extend_start_date' => $oldEndDate,

                'extend_end_date' => $request->new_end_date,

                'end_date' => $request->new_end_date,

                'extend_reason' => $request->extend_reason,

                'extend_note' => $request->extend_note,

            ]);

            ContractHistory::create([

                'contract_id' => $contract->id,

                'user_id' => Auth::id(),

                'action' => 'extend',

                'reason' => $request->extend_reason,

                'description' => 'Gia hạn hợp đồng',

                'old_data' => [

                    'end_date' => $oldEndDate,

                ],

                'new_data' => [

                    'end_date' => $request->new_end_date,

                ],

            ]);

        });

        return back()->with(
            'success',
            'Gia hạn hợp đồng thành công.'
        );
    }
    public function print($id)
    {
        $contract = Contract::with([
            'room',
            'tenant'
        ])->findOrFail($id);

        return view(
            'admin.contracts.print',
            compact('contract')
        );
    }
    public function sendSignature(Contract $contract)
    {
        if (!$contract->isDraft()) {
            return back()->with(
                'error',
                'Chỉ hợp đồng ở trạng thái Draft mới có thể gửi ký.'
            );
        }

        $contract->update([
            'status' => Contract::STATUS_PENDING_SIGNATURE,
        ]);

        ContractHistory::create([
            'contract_id' => $contract->id,
            'user_id' => Auth::id(),
            'action' => 'send_signature',
            'description' => 'Gửi hợp đồng cho khách thuê ký',
            'old_data' => [
                'status' => Contract::STATUS_DRAFT,
            ],
            'new_data' => [
                'status' => Contract::STATUS_PENDING_SIGNATURE,
            ],
        ]);

        return back()->with(
            'success',
            'Đã gửi hợp đồng cho khách thuê ký.'
        );
    }

    public function recallSignature(Contract $contract)
    {
        if (!$contract->isPendingSignature()) {
            return back()->with(
                'error',
                'Chỉ hợp đồng đang chờ ký mới được thu hồi.'
            );
        }

        DB::transaction(function () use ($contract) {

            $contract->update([
                'status' => Contract::STATUS_DRAFT,
            ]);

            ContractHistory::create([
                'contract_id' => $contract->id,
                'user_id'     => Auth::id(),
                'action'      => 'recall_signature',
                'description' => 'Thu hồi hợp đồng để chỉnh sửa',

                'old_data' => [
                    'status' => Contract::STATUS_PENDING_SIGNATURE,
                ],

                'new_data' => [
                    'status' => Contract::STATUS_DRAFT,
                ],
            ]);
        });

        return back()->with(
            'success',
            'Đã thu hồi hợp đồng thành công. Bạn có thể chỉnh sửa và gửi lại.'
        );
    }
    public function confirmSignature(Contract $contract)
    {
        if (!$contract->isPendingSignature()) {

            return back()->with(
                'error',
                'Chỉ hợp đồng đang chờ ký mới có thể xác nhận.'
            );

        }

        $contract->update([

            'status' => Contract::STATUS_SIGNED,

            'signed_at' => now(),

        ]);

        return back()->with(
            'success',
            'Đã xác nhận khách thuê ký hợp đồng.'
        );
    }
    public function confirmDeposit(Contract $contract)
    {
        if (!$contract->isSigned()) {

            return back()->with(
                'error',
                'Chỉ hợp đồng đã ký mới có thể xác nhận đóng cọc.'
            );

        }

        $contract->update([

            'status'            => Contract::STATUS_DEPOSIT_PAID,

            'deposit_status'    => Contract::DEPOSIT_PAID,

            'deposit_paid_at'   => now(),

        ]);

        return back()->with(
            'success',
            'Đã xác nhận khách thuê đóng tiền cọc.'
        );
    }
    public function returnDeposit(Request $request, Contract $contract)
    {
        if (!$contract->canReturnDeposit()) {

            return back()->with(
                'error',
                'Hợp đồng này chưa thể hoàn tiền cọc.'
            );
        }

        $request->validate([

            'return_reason' => 'required|string|max:255',

            'return_note' => 'nullable|string',

        ]);

        DB::transaction(function () use ($contract, $request) {

            $oldDepositStatus = $contract->deposit_status;

            $contract->update([

                'status' => Contract::STATUS_DEPOSIT_RETURNED,

                'deposit_status' => Contract::DEPOSIT_RETURNED,

            ]);

            ContractHistory::create([

                'contract_id' => $contract->id,

                'user_id' => Auth::id(),

                'action' => 'return_deposit',

                'reason' => $request->return_reason,

                'description' => 'Hoàn tiền cọc',

                'old_data' => [

                    'deposit_status' => $oldDepositStatus,

                ],

                'new_data' => [

                    'deposit_status' => Contract::DEPOSIT_RETURNED,

                ],

            ]);

        });

        return back()->with(
            'success',
            'Đã hoàn tiền cọc thành công.'
        );
    }

    
    public function activate(Contract $contract)
    {
        if ($contract->status !== Contract::STATUS_DEPOSIT_PAID) {

            return back()->with(
                'error',
                'Chỉ hợp đồng đã đóng cọc mới được kích hoạt.'
            );

        }

        $contract->update([

            'status' => Contract::STATUS_ACTIVE,

        ]);

        return back()->with(
            'success',
            'Kích hoạt hợp đồng thành công.'
        );
    }
    public function destroy(Contract $contract)
    {
        $room = $contract->room;

        $contract->delete();

        $exists = Contract::where('room_id', $room->id)
            ->whereIn('status',[
                Contract::STATUS_DRAFT,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_SIGNED,
                Contract::STATUS_DEPOSIT_PAID,
                Contract::STATUS_ACTIVE
            ])
            ->exists();

        if(!$exists){
            $room->update([
                'status'=>Room::STATUS_AVAILABLE,
                'current_people'=>0
            ]);
        }

        return back()->with('success','Đã xóa hợp đồng.');
    }

}
