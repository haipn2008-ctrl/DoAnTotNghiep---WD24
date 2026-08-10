<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantAccountLifecycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->whereHas('user', fn ($query) => $query->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE]))
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', ['pending', 'active']))
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
            'number_of_people' => ['nullable', 'integer', 'min:1', 'max:20'],
        ], $this->messages());

        DB::transaction(function () use ($data) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            $tenant = Tenant::with('user')->lockForUpdate()->findOrFail($data['tenant_id']);

            if ($room->status !== 'available' || $room->contracts()->whereIn('status', ['pending', 'active'])->exists()) {
                throw ValidationException::withMessages([
                    'room_id' => 'Phòng đang có người thuê hoặc không sẵn sàng cho thuê.',
                ]);
            }

            $numberOfPeople = $data['number_of_people'] ?? 1;

            if ($numberOfPeople > $room->max_people) {
                throw ValidationException::withMessages([
                    'number_of_people' => 'Số người không được vượt quá sức chứa của phòng.',
                ]);
            }

            if (! $tenant->user || ! in_array($tenant->user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true)) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Khách thuê đã rời đi, đang quyết toán hoặc tài khoản không còn hợp lệ. Hãy tạo hồ sơ và tài khoản mới cho khách mới.',
                ]);
            }

            if ($tenant->contracts()->whereIn('status', ['pending', 'active'])->exists()) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Khách thuê đã có hợp đồng đang hoạt động hoặc đang chờ ký.',
                ]);
            }

            $contract = Contract::create([
                'contract_code' => 'TMP-'.Str::uuid(),
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'number_of_people' => $numberOfPeople,
                'signed_at' => now(),
                'status' => Contract::STATUS_ACTIVE,
            ]);

            $contract->update([
                'contract_code' => 'HD'.str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT),
            ]);

            $room->update([
                'status' => Room::STATUS_OCCUPIED,
                'current_people' => $numberOfPeople,
            ]);
        });

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Hợp đồng đã có hiệu lực.');
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
        $tenant = $contract->tenant;
        $data = $request->validate([
            'full_name' => ['required', 'max:255'],
            'cccd' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenant->id)],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/', Rule::unique('tenants', 'phone')->ignore($tenant->id)],
            'email' => ['nullable', 'email', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'address' => ['nullable', 'string'],
        ], $this->messages());

        $tenant->update($data);

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
            'confirm_end' => ['accepted'],
        ], $this->messages());

        $accountStatus = DB::transaction(function () use ($data, $id) {
            $contract = Contract::with(['room', 'tenant.user'])->lockForUpdate()->findOrFail($id);

            if ($contract->status !== 'active') {
                throw ValidationException::withMessages([
                    'contract' => 'Chỉ có thể kết thúc hợp đồng đang hiệu lực.',
                ]);
            }

            $actualEndDate = Carbon::parse($data['actual_end_date'])->startOfDay();

            if ($actualEndDate->lt($contract->start_date->startOfDay()) || $actualEndDate->isFuture()) {
                throw ValidationException::withMessages([
                    'actual_end_date' => 'Ngày trả phòng phải từ ngày bắt đầu hợp đồng đến ngày hiện tại.',
                ]);
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

            return app(TenantAccountLifecycle::class)->sync($contract->tenant);
        });

        $message = match ($accountStatus) {
            User::STATUS_SETTLING => 'Kết thúc hợp đồng thành công. Tài khoản khách được giữ ở chế độ quyết toán do còn công nợ.',
            User::STATUS_ACTIVE, User::STATUS_PENDING => 'Kết thúc hợp đồng thành công. Tài khoản khách vẫn được giữ do còn hợp đồng khác.',
            default => 'Kết thúc hợp đồng thành công. Tài khoản khách đã ngừng hoạt động.',
        };

        return redirect()
            ->route('admin.contracts.end.list')
            ->with('success', $message);
    }

    public function file(Contract $contract): StreamedResponse
    {
        abort_unless($contract->contract_file && Storage::disk('local')->exists($contract->contract_file), 404);

        return Storage::disk('local')->response($contract->contract_file);
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

        abort_unless($contract->status === Contract::STATUS_ACTIVE, 409, 'Chỉ hợp đồng đang hiệu lực mới có thể kết thúc.');

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

        abort_unless($contract->status === Contract::STATUS_ACTIVE, 409, 'Chỉ hợp đồng đang hiệu lực mới có thể gia hạn.');

        return view('admin.contracts.extend-form', compact('contract'));
    }

    public function extend(Request $request, $id)
    {
        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'extend_reason' => ['required', Rule::in(['tenant_request', 'renew_contract', 'agreement', 'other'])],
            'extend_note' => ['nullable', 'string'],
            'confirm_extend' => ['accepted'],
        ], $this->messages());

        DB::transaction(function () use ($data, $id) {
            $contract = Contract::lockForUpdate()->findOrFail($id);

            if ($contract->status !== Contract::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['contract' => 'Chỉ có thể gia hạn hợp đồng đang hiệu lực.']);
            }

            $newEndDate = Carbon::parse($data['new_end_date'])->startOfDay();
            if (! $newEndDate->gt($contract->end_date->startOfDay())) {
                throw ValidationException::withMessages(['new_end_date' => 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.']);
            }

            $oldEndDate = $contract->end_date->copy();
            $contract->update([
                'end_date' => $newEndDate,
                'extended_at' => now(),
                'extend_start_date' => $oldEndDate->addDay(),
                'extend_end_date' => $newEndDate,
                'extend_reason' => $data['extend_reason'],
                'extend_note' => $data['extend_note'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.contracts.extend.list')
            ->with('success', 'Gia hạn hợp đồng thành công.');
    }

    public function destroy(Contract $contract)
    {
        if ($contract->status !== Contract::STATUS_PENDING || $contract->invoices()->exists()) {
            return back()->with('error', 'Không thể xóa hợp đồng đang hiệu lực.');
        }

        $contract->delete();

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Xóa hợp đồng thành công.');
    }

    private function contractQuery(Request $request)
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Contract::STATUS_PENDING, Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_TERMINATED])],
        ]);
        $query = Contract::with(['room', 'tenant']);

        if (! empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);
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

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
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
            $code = 'HD'.str_pad($lastId, 3, '0', STR_PAD_LEFT);
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
