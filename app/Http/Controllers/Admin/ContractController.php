<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractOccupant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContractIdentityDocumentService;
use App\Services\ContractLifecycleService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractLifecycleService $lifecycle,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function index(Request $request)
    {
        $contracts = $this->contractQuery($request)->latest()->get();

        if ($request->ajax()) {
            return view('admin.contracts.partials.results', compact('contracts'));
        }

        return view('admin.contracts.index', compact('contracts'));
    }

    public function create()
    {
        $rooms = Room::query()->with('activeContract')->where('status', '!=', Room::STATUS_MAINTENANCE)->orderBy('room_code')->get();
        $tenants = Tenant::query()->with('user:id,email')
            ->whereHas('user', fn ($query) => $query->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE]))
            ->orderBy('full_name')->get();

        return view('admin.contracts.create', compact('rooms', 'tenants'));
    }

    public function store(Request $request)
    {
        $data = $this->contractData($request);
        $storedPaths = [];
        try {
            $contract = DB::transaction(function () use ($data, $request, &$storedPaths): Contract {
                $contract = $this->lifecycle->createDraft($data, $request->user());
                $this->storeSubmittedIdentityDocuments($contract, $data, $request->user(), $storedPaths);

                return $contract;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        $message = 'Đã tạo bản nháp. Phòng chưa bị chiếm và hợp đồng chưa được ký.';
        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('admin.contracts.show', $contract),
            ], 201);
        }

        return redirect()->route('admin.contracts.show', $contract)->with('success', $message);
    }

    public function show(Contract $contract)
    {
        $contract->load([
            'room', 'tenant.user', 'invoices.payments',
            'occupants.histories.performer', 'occupants.tenant',
            'statusHistories.performer', 'signedConfirmer', 'moveInTermsConfirmer', 'moveInDetailsConfirmer',
            'handoverItems', 'checkedInBy', 'checkedOutBy',
            'cancelledBy', 'completedBy', 'lifecycleAlerts' => fn ($query) => $query->whereNull('resolved_at')->latest('detected_at'),
        ]);
        $readings = $contract->utilityReadings()->orderBy('record_date')->orderBy('id')->get();
        $handoverReading = $readings->firstWhere('reading_type', 'handover');
        $checkoutReading = $readings->where('reading_type', 'checkout')->last();
        $latestReading = $readings->last();
        $setting = Setting::currentOrCreate();
        $totalInvoiced = (float) $contract->invoices->where('status', '!=', Invoice::STATUS_WRITTEN_OFF)->sum('total_amount');
        $totalPaid = (float) $contract->invoices->flatMap->payments->where('status', Payment::STATUS_SUCCESS)->sum('amount_paid');
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);
        $depositPaid = $contract->deposit_paid_amount;
        $depositRemaining = $contract->deposit_remaining_amount;

        return view('admin.contracts.show', compact(
            'contract', 'handoverReading', 'checkoutReading', 'latestReading', 'setting',
            'totalInvoiced', 'totalPaid', 'totalOutstanding', 'depositPaid', 'depositRemaining'
        ));
    }

    public function edit(Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless($contract->status === Contract::STATUS_DRAFT, 409, 'Chỉ bản nháp mới được sửa.');
        $contract->load('occupants');
        $rooms = Room::query()->with('activeContract')->where('status', '!=', Room::STATUS_MAINTENANCE)->orderBy('room_code')->get();
        $tenants = Tenant::query()->with('user:id,email')
            ->whereHas('user', fn ($query) => $query->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE]))
            ->orderBy('full_name')->get();

        return view('admin.contracts.edit', compact('contract', 'rooms', 'tenants'));
    }

    public function update(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $this->contractData($request, true, $contract);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($contract, $data, $request, &$storedPaths): void {
                $this->lifecycle->updateDraft($contract, $request->user(), $data);
                $this->storeSubmittedIdentityDocuments($contract, $data, $request->user(), $storedPaths);
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        $message = 'Đã cập nhật bản nháp.';
        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('admin.contracts.show', $contract),
            ]);
        }

        return redirect()->route('admin.contracts.show', $contract)->with('success', $message);
    }

    public function submitForSignature(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $this->lifecycle->submitForSignature($contract, $request->user(), $data['reason'] ?? null);

        return back()->with('success', 'Hợp đồng đã chuyển sang chờ ký.');
    }

    public function returnToDraft(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->lifecycle->returnToDraft($contract, $request->user(), $data['reason']);

        return back()->with('success', 'Hợp đồng đã được trả lại bản nháp.');
    }

    public function markAsSigned(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'signed_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->lifecycle->markAsSigned($contract, $request->user(), $data['signed_at'], $data['reason'] ?? null);

        return back()->with('success', 'Đã xác nhận hợp đồng được ký và giữ lịch phòng.');
    }

    public function issueDepositInvoice(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        try {
            $invoice = $this->lifecycle->issueDepositInvoice($contract, $request->user());
        } catch (QueryException $exception) {
            $invoice = $contract->invoices()->where('lifecycle_event_key', "contract:{$contract->id}:deposit")->first();
            if (! $invoice) {
                throw $exception;
            }
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Đã phát hành hóa đơn cọc.');
    }

    public function checkIn(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'actual_move_in_at' => ['required', 'date', 'before_or_equal:now'],
            'handover_electricity' => ['required', 'integer', 'min:0'],
            'handover_water' => ['required', 'integer', 'min:0'],
            'handover_confirmed' => ['accepted'],
            'schedule_variance_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->lifecycle->checkIn($contract, $request->user(), $data);

        return back()->with('success', 'Check-in thành công. Phòng đã chuyển sang có người ở.');
    }

    public function extendMoveInDeadline(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'reservation_expires_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->lifecycle->extendMoveInDeadline($contract, $request->user(), $data['reservation_expires_at'], $data['reason']);

        return back()->with('success', 'Đã gia hạn thời gian giữ phòng.');
    }

    public function cancel(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:2000']]);
        $this->lifecycle->cancel($contract, $request->user(), $data['cancel_reason']);

        return back()->with('success', 'Đã hủy hợp đồng và giữ nguyên toàn bộ lịch sử.');
    }

    public function checkOut(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'actual_move_out_at' => ['required', 'date', 'before_or_equal:now'],
            'checkout_electricity' => ['required', 'integer', 'min:0'],
            'checkout_water' => ['required', 'integer', 'min:0'],
            'checkout_reason' => ['required', 'string', 'max:2000'],
            'settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'settlement_description' => [Rule::requiredIf(fn () => (float) $request->input('settlement_amount', 0) > 0), 'nullable', 'string', 'max:1000'],
        ]);
        $this->lifecycle->checkOut($contract, $request->user(), $data);

        return redirect()->route('admin.contracts.show', $contract)->with('success', 'Đã checkout. Hợp đồng đang chờ quyết toán.');
    }

    /** Route cũ được giữ tương thích nhưng thực hiện đúng nghiệp vụ checkout mới. */
    public function end(Request $request, $id)
    {
        $request->merge([
            'actual_move_out_at' => $request->input('actual_move_out_at', $request->input('actual_end_date')),
            'checkout_reason' => $request->input('checkout_reason', trim(($request->input('termination_reason') ?? '').' '.($request->input('termination_note') ?? ''))),
        ]);

        return $this->checkOut($request, Contract::findOrFail($id));
    }

    public function completeSettlement(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'deposit_resolution' => ['nullable', Rule::in([Contract::DEPOSIT_REFUNDED, Contract::DEPOSIT_DEDUCTED, Contract::DEPOSIT_RETAINED])],
            'settlement_note' => ['nullable', 'string', 'max:2000'],
            'write_off_outstanding' => ['nullable', 'boolean'],
            'write_off_reason' => ['nullable', 'required_if:write_off_outstanding,1', 'string', 'max:2000'],
            'confirm_complete' => ['accepted'],
        ]);
        $this->lifecycle->completeSettlement($contract, $request->user(), $data);

        return back()->with('success', 'Đã hoàn tất quyết toán hợp đồng.');
    }

    public function extend(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'new_end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'extend_reason' => ['nullable', 'string', 'max:2000'],
            'extend_note' => ['nullable', 'string', 'max:2000'],
            'confirm_extend' => ['sometimes', 'accepted'],
        ]);
        $reason = $data['reason'] ?? trim(($data['extend_reason'] ?? '').' '.($data['extend_note'] ?? ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Gia hạn hợp đồng bắt buộc có lý do.'])->withInput();
        }
        $this->lifecycle->extendContract($contract, $request->user(), $data['new_end_date'], $reason);

        return redirect()->route('admin.contracts.show', $contract)->with('success', 'Đã gia hạn hợp đồng.');
    }

    public function print($id)
    {
        $contract = Contract::with(['room', 'tenant', 'representativeOccupant', 'handoverItems'])->findOrFail($id);
        $setting = Setting::currentOrCreate();

        return view('admin.contracts.print', compact('contract', 'setting'));
    }

    public function file(Contract $contract): StreamedResponse
    {
        abort_unless($contract->contractFileExists(), 404);

        return Storage::disk('local')->response($contract->contract_file);
    }

    public function identityDocument(ContractOccupant $occupant, string $side): StreamedResponse
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $path = $side === 'front' ? $occupant->identity_front_path : $occupant->identity_back_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $label = $side === 'front' ? 'mat-truoc' : 'mat-sau';

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        return Storage::disk('local')->response($path, "CCCD-{$label}-{$occupant->contract_id}.{$extension}");
    }

    public function endList(Request $request)
    {
        $contracts = $this->contractQuery($request)->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->orderBy('end_date')->get();

        return view('admin.contracts.end', compact('contracts'));
    }

    public function endForm($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);
        abort_unless(in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true), 409);
        $latestReading = $contract->utilityReadings()->latest('record_date')->latest('id')->first();

        return view('admin.contracts.end-form', compact('contract', 'latestReading'));
    }

    public function extendList(Request $request)
    {
        $contracts = $this->contractQuery($request)->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)->orderBy('end_date')->get();

        return view('admin.contracts.extend', compact('contracts'));
    }

    public function extendForm($id)
    {
        $contract = Contract::with(['room', 'tenant'])->findOrFail($id);
        abort_unless(in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true), 409);

        return view('admin.contracts.extend-form', compact('contract'));
    }

    private function contractData(Request $request, bool $editing = false, ?Contract $contract = null): array
    {
        $this->mergeCalculatedContractDates($request);
        $representativeTenant = Tenant::query()->with('user')->find($request->input('tenant_id'));
        $existingRepresentative = $contract?->representativeOccupant()->first();
        $hasExistingIdentityPair = $existingRepresentative?->identity_front_path
            && $existingRepresentative?->identity_back_path;
        $request->merge([
            'representative' => array_merge([
                'full_name' => $representativeTenant?->full_name,
                'date_of_birth' => $representativeTenant?->date_of_birth?->toDateString(),
                'gender' => $representativeTenant?->gender,
                'cccd' => $representativeTenant?->cccd,
                'phone' => $representativeTenant?->phone,
                'address' => $representativeTenant?->address,
            ], (array) $request->input('representative', [])),
        ]);
        $request->merge([
            'occupants' => collect($request->input('occupants', []))
                ->filter(fn ($occupant): bool => filled($occupant['full_name'] ?? null))
                ->all(),
        ]);

        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'representative_is_occupant' => ['nullable', 'boolean'],
            'representative.full_name' => ['required', 'string', 'max:255'],
            'representative.date_of_birth' => ['nullable', 'date', 'before:today'],
            'representative.gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'representative.cccd' => [
                'required', 'digits:12',
                Rule::unique('tenants', 'cccd')->ignore($representativeTenant?->id),
            ],
            'representative.identity_front' => [
                Rule::requiredIf(! $editing || ! $hasExistingIdentityPair || $request->hasFile('representative.identity_back')),
                'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            ],
            'representative.identity_back' => [
                Rule::requiredIf(! $editing || ! $hasExistingIdentityPair || $request->hasFile('representative.identity_front')),
                'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            ],
            'representative.phone' => [
                'required', 'regex:/^[0-9]{10,15}$/',
                Rule::unique('tenants', 'phone')->ignore($representativeTenant?->id),
                Rule::unique('users', 'phone')->ignore($representativeTenant?->user_id),
            ],
            'representative.address' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'contract_duration' => ['required', 'string', Rule::in(['short_term', '3', '6', '12'])],
            'end_date' => ['required', 'date', 'after:start_date'],
            'scheduled_move_in_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:reservation_expires_at'],
            'reservation_expires_at' => ['required', 'date', 'after_or_equal:scheduled_move_in_date', 'before_or_equal:end_date'],
            'move_in_terms_confirmed' => ['exclude_unless:contract_duration,short_term', 'accepted'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'occupants' => ['nullable', 'array', 'max:100'],
            'occupants.*.id' => ['nullable', 'integer', Rule::exists('contract_occupants', 'id')->where(
                fn ($query) => $contract ? $query->where('contract_id', $contract->id) : $query->whereRaw('1 = 0')
            )],
            'occupants.*.full_name' => ['required', 'string', 'max:150'],
            'occupants.*.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'occupants.*.identity_number' => ['required', 'digits:12', 'distinct'],
            'occupants.*.identity_front' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'occupants.*.identity_back' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'occupants.*.phone' => ['nullable', 'string', 'max:30'],
            'internet_enabled' => ['nullable', 'boolean'],
            'service_enabled' => ['nullable', 'boolean'],
            'parking_quantity' => ['nullable', 'integer', 'min:0', 'max:20'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$editing ? 'nullable' : 'exclude', 'string', 'max:1000'],
        ], [
            'end_date.after' => 'Ngày kết thúc phải sau ngày bắt đầu.',
            'contract_duration.required' => 'Vui lòng chọn thời hạn hợp đồng.',
            'contract_duration.in' => 'Thời hạn hợp đồng không hợp lệ.',
            'scheduled_move_in_date.required' => 'Vui lòng nhập ngày dự kiến nhận phòng.',
            'scheduled_move_in_date.after_or_equal' => 'Ngày dự kiến nhận phòng không được trước ngày bắt đầu thời hạn thuê.',
            'scheduled_move_in_date.before_or_equal' => 'Ngày dự kiến nhận phòng không được sau hạn cuối nhận phòng.',
            'reservation_expires_at.required' => 'Vui lòng chọn hạn cuối phải nhận phòng.',
            'reservation_expires_at.after_or_equal' => 'Hạn cuối nhận phòng không được trước ngày dự kiến nhận phòng.',
            'reservation_expires_at.before_or_equal' => 'Hạn cuối nhận phòng không được sau ngày kết thúc hợp đồng.',
            'move_in_terms_confirmed.accepted' => 'Admin phải xác nhận đã trao đổi và thống nhất lịch nhận phòng với khách.',
            'occupants.max' => 'Danh sách người ở vượt quá giới hạn xử lý cho phép.',
            'occupants.*.full_name.required' => 'Vui lòng nhập họ và tên người ở.',
            'occupants.*.full_name.max' => 'Họ và tên người ở không được vượt quá 150 ký tự.',
            'occupants.*.date_of_birth.date' => 'Ngày sinh người ở không đúng định dạng.',
            'occupants.*.date_of_birth.before_or_equal' => 'Ngày sinh người ở không được ở tương lai.',
            'occupants.*.identity_number.required' => 'Vui lòng nhập CCCD của người ở.',
            'occupants.*.identity_number.digits' => 'CCCD người ở phải gồm đúng 12 chữ số.',
            'occupants.*.identity_number.distinct' => 'CCCD người ở bị trùng trong danh sách.',
            'occupants.*.identity_front.required' => 'Vui lòng chọn ảnh mặt trước CCCD của người ở.',
            'occupants.*.identity_front.file' => 'Ảnh mặt trước CCCD tải lên không hợp lệ.',
            'occupants.*.identity_front.image' => 'Mặt trước CCCD phải là một tệp ảnh.',
            'occupants.*.identity_front.mimes' => 'Ảnh mặt trước CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'occupants.*.identity_front.max' => 'Ảnh mặt trước CCCD không được lớn hơn 5 MB.',
            'occupants.*.identity_back.required' => 'Vui lòng chọn ảnh mặt sau CCCD của người ở.',
            'occupants.*.identity_back.file' => 'Ảnh mặt sau CCCD tải lên không hợp lệ.',
            'occupants.*.identity_back.image' => 'Mặt sau CCCD phải là một tệp ảnh.',
            'occupants.*.identity_back.mimes' => 'Ảnh mặt sau CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'occupants.*.identity_back.max' => 'Ảnh mặt sau CCCD không được lớn hơn 5 MB.',
            'occupants.*.phone.max' => 'Số điện thoại người ở không được vượt quá 30 ký tự.',
            'representative.cccd.required' => 'Vui lòng bổ sung CCCD của người đại diện trước khi tạo hợp đồng.',
            'representative.cccd.digits' => 'CCCD người đại diện phải gồm đúng 12 chữ số.',
            'representative.cccd.unique' => 'CCCD người đại diện đã thuộc hồ sơ khách thuê khác.',
            'representative.identity_front.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'representative.identity_front.image' => 'Mặt trước CCCD người đại diện phải là một tệp ảnh.',
            'representative.identity_front.mimes' => 'Ảnh mặt trước CCCD người đại diện chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'representative.identity_front.max' => 'Ảnh mặt trước CCCD người đại diện không được lớn hơn 5 MB.',
            'representative.identity_back.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'representative.identity_back.image' => 'Mặt sau CCCD người đại diện phải là một tệp ảnh.',
            'representative.identity_back.mimes' => 'Ảnh mặt sau CCCD người đại diện chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'representative.identity_back.max' => 'Ảnh mặt sau CCCD người đại diện không được lớn hơn 5 MB.',
        ]);

        if ($data['contract_duration'] === 'short_term') {
            $maximumEndDate = Carbon::parse($data['start_date'])->addMonthsNoOverflow(3);
            if (Carbon::parse($data['end_date'])->gt($maximumEndDate)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Thuê ít ngày không được chọn ngày kết thúc quá 3 tháng kể từ ngày bắt đầu.',
                ]);
            }
        }
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $deadline = Carbon::parse($data['reservation_expires_at'])->endOfDay();
        $data['reservation_expires_at'] = $deadline;
        $totalDays = max(1, $start->diffInDays($end));
        $data['move_in_window_ratio'] = round($start->diffInDays($deadline->copy()->startOfDay()) / $totalDays, 4);

        // Giữ nguyên key trong lúc validation để input chữ và file CCCD cùng index.
        // Chỉ chuẩn hóa thành mảng liên tục sau khi Laravel đã ghép input với files.
        $data['occupants'] = array_values($data['occupants'] ?? []);
        foreach ($data['occupants'] as $index => $occupantData) {
            $existing = filled($occupantData['id'] ?? null)
                ? ContractOccupant::query()->where('contract_id', $contract?->id)->find($occupantData['id'])
                : null;
            $hasStoredPair = $existing?->identity_front_path && $existing?->identity_back_path;
            $identityChanged = $existing
                && (string) $existing->identity_number !== (string) ($occupantData['identity_number'] ?? '');
            $hasFront = isset($occupantData['identity_front']);
            $hasBack = isset($occupantData['identity_back']);
            $requiresNewPair = ! $hasStoredPair || $identityChanged || $hasFront || $hasBack;
            $identityErrors = [];
            if ($requiresNewPair && ! $hasFront) {
                $identityErrors["occupants.{$index}.identity_front"] = 'Vui lòng chọn ảnh mặt trước CCCD của người ở.';
            }
            if ($requiresNewPair && ! $hasBack) {
                $identityErrors["occupants.{$index}.identity_back"] = 'Vui lòng chọn ảnh mặt sau CCCD của người ở.';
            }
            if ($identityErrors) {
                throw ValidationException::withMessages($identityErrors);
            }
        }
        $data['representative_is_occupant'] = $request->boolean('representative_is_occupant');
        $data['number_of_people'] = count($data['occupants']) + (int) $data['representative_is_occupant'];

        return $data;
    }

    private function mergeCalculatedContractDates(Request $request): void
    {
        $startDate = $request->input('start_date');
        $duration = $request->input('contract_duration');
        if (! is_string($startDate)) {
            return;
        }

        try {
            $start = Carbon::createFromFormat('Y-m-d', $startDate);
        } catch (\Throwable) {
            return;
        }
        if (! $start || $start->toDateString() !== $startDate) {
            return;
        }
        $start->startOfDay();

        $calculated = [];
        if (in_array((string) $duration, ['3', '6', '12'], true)) {
            $calculated['end_date'] = $start->copy()->addMonthsNoOverflow((int) $duration)->toDateString();
            $calculated['reservation_expires_at'] = $start->copy()->addDays(10)->toDateString();
        }
        $request->merge($calculated);
    }

    private function storeSubmittedIdentityDocuments(Contract $contract, array $data, User $actor, array &$storedPaths): void
    {
        if (isset($data['representative']['identity_front'], $data['representative']['identity_back'])) {
            $representative = $contract->occupants()->where('role', ContractOccupant::ROLE_REPRESENTATIVE)
                ->lockForUpdate()->latest('id')->firstOrFail();
            $this->identityDocuments->storePair(
                $representative,
                $data['representative']['identity_front'],
                $data['representative']['identity_back'],
                $actor,
                $storedPaths,
            );
        }

        foreach ($data['occupants'] as $occupantData) {
            if (! isset($occupantData['identity_front'], $occupantData['identity_back'])) {
                continue;
            }
            $occupant = $contract->occupants()->where('role', ContractOccupant::ROLE_OCCUPANT)
                ->current()->where('identity_number', $occupantData['identity_number'])
                ->lockForUpdate()->latest('id')->firstOrFail();
            $this->identityDocuments->storePair(
                $occupant,
                $occupantData['identity_front'],
                $occupantData['identity_back'],
                $actor,
                $storedPaths,
            );
        }
    }

    private function contractQuery(Request $request)
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                Contract::STATUS_DRAFT, Contract::STATUS_PENDING_SIGNATURE, Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN, Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED,
                Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED, Contract::STATUS_CANCELLED,
            ])],
        ]);
        $query = Contract::with(['room', 'tenant']);
        if (filled($filters['keyword'] ?? null)) {
            $keyword = trim($filters['keyword']);
            $query->where(fn ($q) => $q->where('contract_code', 'like', "%{$keyword}%")
                ->orWhereHas('tenant', fn ($tenant) => $tenant->where('full_name', 'like', "%{$keyword}%")->orWhere('phone', 'like', "%{$keyword}%"))
                ->orWhereHas('room', fn ($room) => $room->where('room_code', 'like', "%{$keyword}%")));
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }
}
