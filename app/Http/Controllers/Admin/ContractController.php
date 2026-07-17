<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\ContractHistory;
use Illuminate\Http\Request;
<<<<<<< HEAD
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


=======
use Illuminate\Validation\Rule;
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225

class ContractController extends Controller
{
    public function index(Request $request)
    {
<<<<<<< HEAD
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
=======
        $contracts = $this->contractQuery($request)
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
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

<<<<<<< HEAD
        return view(
            'admin.contracts.index',
            compact(
                'contracts',
                'rooms',
                'tenants',
                'templates'
            )
        );
=======
        return view('admin.contracts.index', compact('contracts'));
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
    }

    public function create()
    {
<<<<<<< HEAD
        // chỉ lấy phòng đang trống
        $rooms = Room::where('status', 'available')
        ->select('id', 'room_code', 'price')
        ->get();
=======
        $rooms = Room::where('status', 'available')
            ->orderBy('room_code')
            ->get();
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225

        $tenants = Tenant::select('id', 'full_name as name')
            ->orderBy('full_name')
            ->get();

        return redirect()
        ->route('admin.contracts.index');
    }

    public function store(Request $request)
    {
<<<<<<< HEAD
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
=======
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'number_of_people' => ['nullable', 'integer', 'min:1', 'max:4'],
        ], $this->messages());

        $room = Room::findOrFail($data['room_id']);

        if ($room->status !== 'available') {
            return back()
                ->withInput()
                ->with('error', 'Phòng đang có người thuê hoặc không sẵn sàng cho thuê.');
        }

        $hasActiveContract = Contract::where('room_id', $room->id)
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($hasActiveContract) {
            return back()
                ->withInput()
                ->with('error', 'Phòng này đã có hợp đồng đang hoạt động hoặc đang chờ ký.');
        }

        Contract::create([
            'contract_code' => $this->nextContractCode(),
            'room_id' => $room->id,
            'tenant_id' => $data['tenant_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'monthly_rent' => $room->price,
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'number_of_people' => $data['number_of_people'] ?? 1,
            'status' => 'pending',
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
        ]);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Hợp đồng đang chờ khách thuê ký.');
    }
<<<<<<< HEAD
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
=======

    public function show(Contract $contract)
    {
        $contract->load(['room', 'tenant']);

        return view('admin.contracts.show', compact('contract'));
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
    }

    public function edit(Contract $contract)
    {
<<<<<<< HEAD
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
=======
        $contract->load('tenant');

        return view('admin.contracts.edit', compact('contract'));
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
    }

    public function update(Request $request, Contract $contract)
    {
<<<<<<< HEAD
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

=======
        $data = $request->validate([
            'full_name' => ['required', 'max:255'],
            'cccd' => ['required', 'max:255'],
            'phone' => ['required', 'max:255'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
        ], $this->messages());

        $contract->tenant->update($data);

        return redirect()
            ->route('admin.contracts.show', $contract)
            ->with('success', 'Cập nhật thông tin người thuê thành công.');
    }

    public function end(Request $request, $id)
    {
        $data = $request->validate([
            'actual_end_date' => ['required', 'date'],
            'termination_reason' => ['required', Rule::in(['expired', 'early', 'violation', 'other'])],
            'termination_note' => ['nullable', 'string'],
        ], $this->messages());

        $contract = Contract::with('room')->findOrFail($id);

        if ($contract->status !== 'active') {
            return redirect()
                ->route('admin.contracts.end.list')
                ->with('error', 'Chỉ có thể kết thúc hợp đồng đang hiệu lực.');
        }

        $contract->update([
            'status' => 'terminated',
            'terminated_at' => $data['actual_end_date'],
            'actual_end_date' => $data['actual_end_date'],
            'termination_reason' => $data['termination_reason'],
            'termination_note' => $data['termination_note'] ?? null,
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
        ]);
        $oldStatus = $contract->status;

<<<<<<< HEAD
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
=======
        $contract->room?->update([
            'status' => 'available',
            'current_people' => 0,
        ]);

        return redirect()
            ->route('admin.contracts.end.list')
            ->with('success', 'Kết thúc hợp đồng thành công.');
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
    }

    public function print($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);

        return view('admin.contracts.print', compact('contract'));
    }
<<<<<<< HEAD
    public function sendSignature(Contract $contract)
    {
        if (!$contract->isDraft()) {
            return back()->with(
                'error',
                'Chỉ hợp đồng ở trạng thái Draft mới có thể gửi ký.'
            );
        }
=======

    public function endList(Request $request)
    {
        $contracts = $this->activeContractQuery($request)
            ->orderBy('end_date')
            ->get();

        return view('admin.contracts.end', compact('contracts'));
    }

    public function endForm($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);

        return view('admin.contracts.end-form', compact('contract'));
    }

    public function extendList(Request $request)
    {
        $contracts = $this->activeContractQuery($request)
            ->orderBy('end_date')
            ->get();

        return view('admin.contracts.extend', compact('contracts'));
    }

    public function extendForm($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);

        return view('admin.contracts.extend-form', compact('contract'));
    }

    public function extend(Request $request, $id)
    {
        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'extend_reason' => ['required', 'string'],
            'extend_note' => ['nullable', 'string'],
        ], $this->messages());

        $contract = Contract::findOrFail($id);
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225

        if ($contract->status !== 'active') {
            return redirect()
                ->route('admin.contracts.extend.list')
                ->with('error', 'Chỉ có thể gia hạn hợp đồng đang hiệu lực.');
        }

        if ($data['new_end_date'] <= $contract->end_date) {
            return back()
                ->withInput()
                ->with('error', 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.');
        }

        $contract->update([
<<<<<<< HEAD
            'status' => Contract::STATUS_PENDING_SIGNATURE,
=======
            'end_date' => $data['new_end_date'],
            'extend_reason' => $data['extend_reason'],
            'extend_note' => $data['extend_note'] ?? null,
>>>>>>> 3bb66892adb64dbcdda16ab528fbe3ec6422a225
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

    public function destroy(Contract $contract)
    {
        if ($contract->status === 'active') {
            return back()->with('error', 'Không thể xóa hợp đồng đang hiệu lực.');
        }

        $contract->delete();

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Xóa hợp đồng thành công.');
    }

    private function contractQuery(Request $request)
    {
        $query = Contract::with(['room', 'tenant']);

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);
            $normalizedCode = strtoupper($keyword);

            $query->where(function ($q) use ($keyword, $normalizedCode) {
                $q->where('contract_code', 'like', "%{$keyword}%")
                    ->orWhere('id', $keyword)
                    ->orWhereHas('tenant', function ($tenant) use ($keyword) {
                        $tenant->where('full_name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%")
                            ->orWhere('cccd', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('room', function ($room) use ($keyword) {
                        $room->where('room_code', 'like', "%{$keyword}%");
                    });

                if (str_starts_with($normalizedCode, 'HD')) {
                    $q->orWhere('contract_code', $normalizedCode);
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function activeContractQuery(Request $request)
    {
        return $this->contractQuery($request)->where('status', 'active');
    }

    private function nextContractCode(): string
    {
        $lastId = (int) Contract::max('id');

        do {
            $lastId++;
            $code = 'HD' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
        } while (Contract::where('contract_code', $code)->exists());

        return $code;
    }

    private function messages(): array
    {
        return [
            'room_id.required' => 'Vui lòng chọn phòng.',
            'room_id.exists' => 'Phòng đã chọn không tồn tại.',
            'tenant_id.required' => 'Vui lòng chọn người thuê.',
            'tenant_id.exists' => 'Người thuê đã chọn không tồn tại.',
            'start_date.required' => 'Vui lòng nhập ngày bắt đầu.',
            'start_date.date' => 'Ngày bắt đầu không hợp lệ.',
            'end_date.required' => 'Vui lòng nhập ngày kết thúc.',
            'end_date.date' => 'Ngày kết thúc không hợp lệ.',
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'deposit_amount.numeric' => 'Tiền cọc phải là số.',
            'deposit_amount.min' => 'Tiền cọc không được nhỏ hơn 0.',
            'number_of_people.integer' => 'Số người phải là số nguyên.',
            'number_of_people.min' => 'Số người phải lớn hơn 0.',
            'number_of_people.max' => 'Số người không được vượt quá 4.',
            'full_name.required' => 'Vui lòng nhập họ tên người thuê.',
            'cccd.required' => 'Vui lòng nhập CCCD.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.email' => 'Email không hợp lệ.',
            'actual_end_date.required' => 'Vui lòng nhập ngày trả phòng thực tế.',
            'actual_end_date.date' => 'Ngày trả phòng thực tế không hợp lệ.',
            'termination_reason.required' => 'Vui lòng chọn lý do kết thúc.',
            'termination_reason.in' => 'Lý do kết thúc không hợp lệ.',
            'new_end_date.required' => 'Vui lòng nhập ngày kết thúc mới.',
            'new_end_date.date' => 'Ngày kết thúc mới không hợp lệ.',
            'extend_reason.required' => 'Vui lòng nhập lý do gia hạn.',
        ];
    }
}
