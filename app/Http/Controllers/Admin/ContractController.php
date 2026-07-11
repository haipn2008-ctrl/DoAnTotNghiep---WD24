<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = $this->contractQuery($request)
            ->latest()
            ->get();

        return view('admin.contracts.index', compact('contracts'));
    }

    public function create()
    {
        $rooms = Room::where('status', 'available')
            ->orderBy('room_code')
            ->get();

        $tenants = Tenant::select('id', 'full_name as name')
            ->orderBy('full_name')
            ->get();

        return view('admin.contracts.create', compact('rooms', 'tenants'));
    }

    public function store(Request $request)
    {
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
        ]);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Hợp đồng đang chờ khách thuê ký.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['room', 'tenant']);

        return view('admin.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $contract->load('tenant');

        return view('admin.contracts.edit', compact('contract'));
    }

    public function update(Request $request, Contract $contract)
    {
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
        ]);

        $contract->room?->update([
            'status' => 'available',
            'current_people' => 0,
        ]);

        return redirect()
            ->route('admin.contracts.end.list')
            ->with('success', 'Kết thúc hợp đồng thành công.');
    }

    public function print($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);

        return view('admin.contracts.print', compact('contract'));
    }

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
            'end_date' => $data['new_end_date'],
            'extend_reason' => $data['extend_reason'],
            'extend_note' => $data['extend_note'] ?? null,
        ]);

        return redirect()
            ->route('admin.contracts.extend.list')
            ->with('success', 'Gia hạn hợp đồng thành công.');
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
