<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityReading;
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

        $handoverSuggestions = UtilityReading::whereIn('room_id', $rooms->pluck('id'))
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->get()
            ->unique('room_id')
            ->mapWithKeys(fn (UtilityReading $reading) => [$reading->room_id => [
                'electricity' => $reading->electricity_new,
                'water' => $reading->water_new,
            ]]);

        $tenants = Tenant::select('id', 'full_name as name')
            ->whereHas('user', fn ($query) => $query->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE]))
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', ['pending', 'active']))
            ->orderBy('full_name')
            ->get();

        return view('admin.contracts.create', compact('rooms', 'tenants', 'handoverSuggestions'));
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
            'handover_electricity' => ['required', 'integer', 'min:0'],
            'handover_water' => ['required', 'integer', 'min:0'],
            'internet_enabled' => ['nullable', 'boolean'],
            'service_enabled' => ['nullable', 'boolean'],
            'parking_quantity' => ['nullable', 'integer', 'min:0', 'max:20'],
        ], $this->messages());

        DB::transaction(function () use ($data) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            $tenant = Tenant::with('user')->lockForUpdate()->findOrFail($data['tenant_id']);

            if ($room->status !== 'available' || $room->contracts()->whereIn('status', ['pending', 'active'])->exists()) {
                throw ValidationException::withMessages([
                    'room_id' => 'Phòng đang có người thuê hoặc không sẵn sàng cho thuê.',
                ]);
            }


            $latestRoomReading = UtilityReading::where('room_id', $room->id)
                ->latest('record_date')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($latestRoomReading
                && ($data['handover_electricity'] < $latestRoomReading->electricity_new
                    || $data['handover_water'] < $latestRoomReading->water_new)) {
                throw ValidationException::withMessages([
                    'handover_electricity' => 'Chỉ số bàn giao không được nhỏ hơn chỉ số gần nhất của phòng.',
                    'handover_water' => 'Chỉ số bàn giao không được nhỏ hơn chỉ số gần nhất của phòng.',
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
                'internet_enabled' => (bool) ($data['internet_enabled'] ?? false),
                'service_enabled' => (bool) ($data['service_enabled'] ?? false),
                'parking_quantity' => $data['parking_quantity'] ?? 0,
                'signed_at' => now(),
                'status' => Contract::STATUS_ACTIVE,
            ]);

            $contract->update([
                'contract_code' => 'HD'.str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT),
            ]);

            $handoverDate = Carbon::parse($data['start_date']);
            UtilityReading::create([
                'room_id' => $room->id,
                'contract_id' => $contract->id,
                'month' => $handoverDate->month,
                'year' => $handoverDate->year,
                'record_date' => $handoverDate->toDateString(),
                'reading_type' => 'handover',
                'electricity_old' => $data['handover_electricity'],
                'electricity_new' => $data['handover_electricity'],
                'water_old' => $data['handover_water'],
                'water_new' => $data['handover_water'],
                'status' => 'confirmed',
                'note' => 'Chỉ số bàn giao khi nhận phòng.',
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
        $contract->load(['room', 'tenant', 'invoices.payments']);
        $readings = UtilityReading::where('room_id', $contract->room_id)
            ->where('contract_id', $contract->id)
            ->orderBy('record_date')->orderBy('id')->get();
        $handoverReading = $readings->firstWhere('reading_type', 'handover');
        $checkoutReading = $readings->where('reading_type', 'checkout')->last();
        $latestReading = $readings->last();
        $setting = Setting::currentOrCreate();
        $totalInvoiced = (float) $contract->invoices->sum('total_amount');
        $totalPaid = (float) $contract->invoices->flatMap->payments
            ->where('status', Payment::STATUS_SUCCESS)->sum('amount_paid');
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);

        return view('admin.contracts.show', compact(
            'contract', 'handoverReading', 'checkoutReading', 'latestReading', 'setting',
            'totalInvoiced', 'totalPaid', 'totalOutstanding'
        ));
    }

    public function issueDepositInvoice(Contract $contract)
    {
        if (! in_array($contract->status, [Contract::STATUS_PENDING, Contract::STATUS_ACTIVE], true)) {
            return back()->with('error', 'Chỉ có thể phát hành hóa đơn cọc cho hợp đồng đang chờ ký hoặc đang hiệu lực.');
        }

        if ((float) $contract->deposit_amount <= 0) {
            return back()->with('error', 'Hợp đồng không có số tiền cọc để phát hành hóa đơn.');
        }

        try {
            $invoice = DB::transaction(function () use ($contract) {
                $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
                $existing = $contract->invoices()
                    ->where('invoice_type', Invoice::TYPE_DEPOSIT)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $invoiceDate = now();
                $dueDays = (int) (Setting::currentOrCreate()->payment_due_days ?? 10);
                $invoice = Invoice::create([
                    'contract_id' => $contract->id,
                    'room_id' => $contract->room_id,
                    'invoice_type' => Invoice::TYPE_DEPOSIT,
                    'invoice_code' => null,
                    'month' => $invoiceDate->month,
                    'year' => $invoiceDate->year,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'due_date' => $invoiceDate->copy()->addDays($dueDays)->toDateString(),
                    'room_fee' => 0,
                    'total_amount' => $contract->deposit_amount,
                    'status' => Invoice::STATUS_UNPAID,
                ]);

                $invoice->update([
                    'invoice_code' => sprintf('DEP-%04d%02d-%06d', $invoiceDate->year, $invoiceDate->month, $invoice->id),
                ]);
                $invoice->details()->create([
                    'type' => Invoice::TYPE_DEPOSIT,
                    'name' => 'Tiền cọc hợp đồng '.$contract->contract_code,
                    'quantity' => 1,
                    'unit' => 'lần',
                    'unit_price' => $contract->deposit_amount,
                    'amount' => $contract->deposit_amount,
                    'note' => 'Khoản cọc nhận phòng',
                    'sort_order' => 1,
                ]);

                return $invoice;
            });
        } catch (\Illuminate\Database\QueryException) {
            $invoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->first();
            if (! $invoice) {
                return back()->with('error', 'Không thể phát hành hóa đơn tiền cọc. Vui lòng thử lại.');
            }
        }

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Phát hành hóa đơn tiền cọc thành công.');
    }

    public function edit(Contract $contract)
    {
        $contract->load('tenant');
        $handoverReading = $contract->utilityReadings()->where('contract_id', $contract->id)
            ->where('reading_type', 'handover')->oldest('record_date')->first();

        return view('admin.contracts.edit', compact('contract', 'handoverReading'));
    }

    public function update(Request $request, Contract $contract)
    {
        $tenant = $contract->tenant;
        $handoverReading = $contract->utilityReadings()->where('contract_id', $contract->id)
            ->where('reading_type', 'handover')->first();
        $data = $request->validate([
            'full_name' => ['required', 'max:255'],
            'cccd' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenant->id)],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/', Rule::unique('tenants', 'phone')->ignore($tenant->id)],
            'email' => ['nullable', 'email', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'address' => ['nullable', 'string'],
            'internet_enabled' => ['nullable', 'boolean'],
            'service_enabled' => ['nullable', 'boolean'],
            'parking_quantity' => ['nullable', 'integer', 'min:0', 'max:20'],
            'handover_electricity' => [$handoverReading ? 'nullable' : 'required', 'integer', 'min:0'],
            'handover_water' => [$handoverReading ? 'nullable' : 'required', 'integer', 'min:0'],
        ], $this->messages());

        $tenant->update(collect($data)->only(['full_name', 'cccd', 'phone', 'email', 'address'])->all());
        $contract->update([
            'internet_enabled' => (bool) ($data['internet_enabled'] ?? false),
            'service_enabled' => (bool) ($data['service_enabled'] ?? false),
            'parking_quantity' => $data['parking_quantity'] ?? 0,
        ]);

        if (! $handoverReading) {
            $handoverDate = Carbon::parse($contract->start_date);
            UtilityReading::create([
                'room_id' => $contract->room_id, 'contract_id' => $contract->id,
                'month' => $handoverDate->month, 'year' => $handoverDate->year,
                'record_date' => $handoverDate->toDateString(), 'reading_type' => 'handover',
                'electricity_old' => $data['handover_electricity'], 'electricity_new' => $data['handover_electricity'],
                'water_old' => $data['handover_water'], 'water_new' => $data['handover_water'],
                'status' => 'confirmed', 'note' => 'Bổ sung chỉ số bàn giao cho hợp đồng hiện có.',
            ]);
        }

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
            'checkout_electricity' => ['required', 'integer', 'min:0'],
            'checkout_water' => ['required', 'integer', 'min:0'],
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

            $lastReading = UtilityReading::where('room_id', $contract->room_id)
                ->where('contract_id', $contract->id)
                ->whereDate('record_date', '<=', $actualEndDate)
                ->latest('record_date')->latest('id')->first();

            if (! $lastReading
                || $data['checkout_electricity'] < $lastReading->electricity_new
                || $data['checkout_water'] < $lastReading->water_new) {
                throw ValidationException::withMessages([
                    'checkout_electricity' => 'Chỉ số trả phòng không được nhỏ hơn chỉ số gần nhất của hợp đồng.',
                ]);
            }

            UtilityReading::create([
                'room_id' => $contract->room_id,
                'contract_id' => $contract->id,
                'month' => $actualEndDate->month,
                'year' => $actualEndDate->year,
                'record_date' => $actualEndDate->toDateString(),
                'reading_type' => 'checkout',
                'electricity_old' => $lastReading->electricity_new,
                'electricity_new' => $data['checkout_electricity'],
                'water_old' => $lastReading->water_new,
                'water_new' => $data['checkout_water'],
                'status' => 'confirmed',
                'note' => 'Chỉ số chốt khi trả phòng.',
            ]);

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

        $latestReading = UtilityReading::where('room_id', $contract->room_id)
            ->where('contract_id', $contract->id)
            ->latest('record_date')
            ->latest('id')
            ->first();

        return view('admin.contracts.end-form', compact('contract', 'latestReading'));
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
