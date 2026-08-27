<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTenant;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\AdultDateOfBirth;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
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
        $rooms = Room::query()->with([
            'activeContract',
            'amenities' => fn ($query) => $query->where('category', Amenity::CATEGORY_ASSET),
        ])->where('status', '!=', Room::STATUS_MAINTENANCE)
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
            ]))
            ->orderBy('room_code')->get();
        $tenants = Tenant::query()->eligibleForContract()->with('user:id,email,status')
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
            'currentMembers.histories.performer', 'currentMembers.tenant.vehicles.tenant',
            'statusHistories.performer', 'signedConfirmer', 'moveInTermsConfirmer', 'moveInDetailsConfirmer',
            'handoverItems', 'checkedInBy', 'checkedOutBy',
            'cancelledBy', 'completedBy', 'lifecycleAlerts' => fn ($query) => $query->whereNull('resolved_at')->latest('detected_at'),
            'settlementStatement.items', 'settlementStatement.invoice',
            'approvedTerminationRequest.processor',
            'representativeTransfers.oldTenant', 'representativeTransfers.newTenant',
            'representativeTransfers.performer',
        ]);
        $readings = $contract->utilityReadings()->orderBy('record_date')->orderBy('id')->get();
        $handoverReading = $readings->firstWhere('reading_type', 'handover');
        $checkoutReading = $readings->where('reading_type', 'checkout')->last();
        $latestReading = $readings->last();
        $baselineReading = $contract->room?->utilityReadings()
            ->where('reading_type', 'baseline')
            ->oldest('record_date')->oldest('id')->first();
        $suggestedHandoverReading = $latestReading ?? $contract->room?->utilityReadings()
            ->latest('record_date')->latest('id')->first();
        $setting = Setting::currentOrCreate();
        $totalInvoiced = (float) $contract->invoices
            ->whereNotIn('status', [Invoice::STATUS_WRITTEN_OFF, Invoice::STATUS_CANCELLED])
            ->sum(fn (Invoice $invoice): float => $invoice->payable_amount);
        $totalPaid = (float) $contract->invoices->flatMap->payments->where('status', Payment::STATUS_SUCCESS)->sum('amount_paid');
        $totalOutstanding = max(0, $totalInvoiced - $totalPaid);
        $depositPaid = $contract->deposit_paid_amount;
        $depositRemaining = $contract->deposit_remaining_amount;

        return view('admin.contracts.show', compact(
            'contract', 'handoverReading', 'checkoutReading', 'latestReading', 'baselineReading',
            'suggestedHandoverReading', 'setting',
            'totalInvoiced', 'totalPaid', 'totalOutstanding', 'depositPaid', 'depositRemaining'
        ));
    }

    public function edit(Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless($contract->status === Contract::STATUS_DRAFT, 409, 'Chỉ bản nháp mới được sửa.');
        $contract->load(['currentMembers.tenant', 'representativeMember.tenant']);
        $rooms = Room::query()->with([
            'activeContract',
            'amenities' => fn ($query) => $query->where('category', Amenity::CATEGORY_ASSET),
        ])->where('status', '!=', Room::STATUS_MAINTENANCE)
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_PENDING_DEPOSIT,
                Contract::STATUS_AWAITING_MOVE_IN,
            ]))
            ->orderBy('room_code')->get();
        $tenants = Tenant::query()->eligibleForContract()->with('user:id,email,status')
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
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_signature_required', 'Hợp đồng đang chờ ký', 'Hợp đồng '.$contract->contract_code.' đã sẵn sàng. Vui lòng mở hợp đồng để kiểm tra và thực hiện ký theo hướng dẫn.');

        return back()->with('success', 'Hợp đồng đã chuyển sang chờ ký.');
    }

    public function returnToDraft(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'edit_after_return' => ['nullable', 'boolean'],
        ]);
        $this->lifecycle->returnToDraft($contract, $request->user(), $data['reason']);
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_returned_to_draft', 'Hợp đồng đang được chỉnh sửa', 'Hợp đồng '.$contract->contract_code.' được chuyển lại bản nháp. Lý do: '.$data['reason']);

        if ($request->boolean('edit_after_return')) {
            return redirect()->route('admin.contracts.edit', $contract)
                ->with('success', 'Hợp đồng đã chuyển về bản nháp. Bạn có thể chỉnh sửa ngay.');
        }

        return back()->with('success', 'Hợp đồng đã được trả lại bản nháp.');
    }

    public function markAsSigned(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $contract->loadMissing('tenant');
        $data = $request->validate([
            'signed_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'signed_contract_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],
        ], [
            'signed_at.required' => 'Vui lòng nhập thời gian ký.',
            'signed_at.date' => 'Thời gian ký không hợp lệ.',
            'signed_at.before_or_equal' => 'Thời gian ký không được ở tương lai.',
        ]);
        $oldPath = $contract->contract_file;
        $newPath = $request->file('signed_contract_file')?->store('contracts/signed', 'local');

        try {
            DB::transaction(function () use ($contract, $request, $data, $newPath): void {
                if ($newPath) {
                    $contract->forceFill(['contract_file' => $newPath])->save();
                }
                $this->lifecycle->markAsSigned($contract, $request->user(), $data['signed_at'], $data['reason'] ?? null);
            }, 3);
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_signed', 'Hợp đồng đã được xác nhận ký', 'Hợp đồng '.$contract->contract_code.' đã được xác nhận ký. Vui lòng theo dõi hóa đơn tiền cọc và thời hạn nhận phòng.');

        return back()->with('success', 'Đã xác nhận hợp đồng được ký và giữ lịch phòng.');
    }

    public function issueDepositInvoice(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $wasIssued = true;
        try {
            $invoice = $this->lifecycle->issueDepositInvoice($contract, $request->user());
        } catch (QueryException $exception) {
            $wasIssued = false;
            $invoice = $contract->invoices()->where('invoice_type', Invoice::TYPE_DEPOSIT)->first();
            if (! $invoice) {
                throw $exception;
            }
        }

        if ($wasIssued) {
            app(ClientNotificationService::class)->invoice($invoice, 'deposit_invoice_issued', 'Hóa đơn tiền cọc đã được phát hành', 'Vui lòng kiểm tra và thanh toán hóa đơn '.$invoice->invoice_code.' đúng hạn.');
        }

        return redirect()->route('admin.contracts.show', $contract)->with('success', 'Đã phát hành hóa đơn tiền cọc.');
    }

    public function checkIn(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'actual_move_in_at' => ['required', 'date', 'before_or_equal:now'],
            'handover_confirmed' => ['accepted'],
            'schedule_variance_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->lifecycle->checkIn($contract, $request->user(), $data);
        app(AdminNotificationService::class)->resolve('move_in_confirmation', $contract);
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_checked_in', 'Đã xác nhận nhận phòng', 'Hợp đồng '.$contract->contract_code.' đã chuyển sang trạng thái đang thuê.');

        return back()->with('success', 'Nhận phòng thành công. Phòng đã chuyển sang có người thuê.');
    }

    public function saveHandoverReading(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $existingReading = $contract->utilityReadings()
            ->where('reading_type', 'handover')
            ->first();
        $data = $request->validate([
            'handover_electricity' => ['required', 'integer', 'min:0'],
            'handover_water' => ['required', 'integer', 'min:0'],
            'handover_electricity_image' => [
                Rule::requiredIf(! $existingReading?->meterImageExists('electricity')),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
            ],
            'handover_water_image' => [
                Rule::requiredIf(! $existingReading?->meterImageExists('water')),
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
            ],
        ], [
            'handover_electricity_image.required' => 'Vui lòng tải ảnh đồng hồ điện để khách đối chiếu.',
            'handover_water_image.required' => 'Vui lòng tải ảnh đồng hồ nước để khách đối chiếu.',
            'handover_electricity_image.image' => 'Ảnh đồng hồ điện không hợp lệ.',
            'handover_water_image.image' => 'Ảnh đồng hồ nước không hợp lệ.',
            'handover_electricity_image.mimes' => 'Ảnh đồng hồ điện phải là JPG, PNG hoặc WEBP.',
            'handover_water_image.mimes' => 'Ảnh đồng hồ nước phải là JPG, PNG hoặc WEBP.',
            'handover_electricity_image.max' => 'Ảnh đồng hồ điện không được lớn hơn 4 MB.',
            'handover_water_image.max' => 'Ảnh đồng hồ nước không được lớn hơn 4 MB.',
        ]);

        $newImages = [];
        foreach (['electricity', 'water'] as $type) {
            $file = $request->file("handover_{$type}_image");
            if ($file) {
                $newImages[$type] = $file->store("utility-readings/{$type}", 'local');
            }
        }

        try {
            $reading = $this->lifecycle->saveHandoverDraft(
                $contract,
                $request->user(),
                (int) $data['handover_electricity'],
                (int) $data['handover_water'],
                $newImages['electricity'] ?? null,
                $newImages['water'] ?? null,
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($newImages));
            throw $exception;
        }

        foreach (['electricity', 'water'] as $type) {
            $oldPath = $existingReading?->{$type.'_image'};
            if (isset($newImages[$type]) && $oldPath && $oldPath !== $reading->{$type.'_image'}) {
                Storage::disk('local')->delete($oldPath);
            }
        }
        app(ClientNotificationService::class)->contract($contract, 'move_in_details_ready', 'Thông tin nhận phòng cần xác nhận', 'Ban quản lý đã cập nhật chỉ số và ảnh đồng hồ điện nước bàn giao. Vui lòng mở hợp đồng để đối chiếu và xác nhận.');

        return back()->with('success', 'Đã lưu chỉ số và ảnh đồng hồ bàn giao. Khách thuê có thể đối chiếu và xác nhận.');
    }

    public function reopenMoveInDetails(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $this->lifecycle->reopenMoveInDetails($contract, $request->user(), $data['reason']);
        app(ClientNotificationService::class)->contract($contract, 'move_in_details_reopened', 'Cần xác nhận lại thông tin nhận phòng', 'Ban quản lý đã cập nhật lại thông tin nhận phòng. Lý do: '.$data['reason']);

        return back()->with('success', 'Đã mở lại thông tin nhận phòng. Sau khi sửa, khách phải xác nhận lại.');
    }

    public function extendMoveInDeadline(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'reservation_expires_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->lifecycle->extendMoveInDeadline($contract, $request->user(), $data['reservation_expires_at'], $data['reason']);
        app(ClientNotificationService::class)->contract($contract->fresh(), 'move_in_deadline_extended', 'Thời hạn nhận phòng đã được gia hạn', 'Thời hạn giữ phòng mới: '.Carbon::parse($data['reservation_expires_at'])->format('H:i d/m/Y').'.');

        return back()->with('success', 'Đã gia hạn thời gian giữ phòng.');
    }

    public function cancel(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate(['cancel_reason' => ['required', 'string', 'max:2000']]);
        $this->lifecycle->cancel($contract, $request->user(), $data['cancel_reason']);
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_cancelled', 'Hợp đồng đã bị hủy', 'Hợp đồng '.$contract->contract_code.' đã bị hủy. Lý do: '.$data['cancel_reason']);

        return back()->with('success', 'Đã hủy hợp đồng và giữ nguyên toàn bộ lịch sử.');
    }

    public function checkOutForm(Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless(
            in_array($contract->status, [
                ...Contract::OPEN_OCCUPANCY_STATUSES,
                Contract::STATUS_SETTLING,
                Contract::STATUS_COMPLETED,
            ], true),
            409,
            'Hợp đồng không ở trạng thái phù hợp với quy trình trả phòng.'
        );

        $contract->load([
            'room', 'tenant.user', 'handoverItems', 'approvedTerminationRequest',
            'invoices.payments', 'settlementStatement.items', 'settlementStatement.invoice',
        ]);
        $latestReading = $contract->utilityReadings()
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->first();
        $openInvoices = $contract->invoices->filter(
            fn (Invoice $invoice) => in_array($invoice->status, [
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_PARTIAL,
            ], true)
        );
        $totalOutstanding = $openInvoices->sum(
            fn (Invoice $invoice): float => $invoice->remaining_amount
        );

        return view('admin.contracts.check-out', compact(
            'contract', 'latestReading', 'openInvoices', 'totalOutstanding'
        ));
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
            'checkout_key_count' => ['required', 'integer', 'min:0', 'max:100'],
            'asset_conditions' => ['nullable', 'array'],
            'asset_conditions.*.condition' => ['required', Rule::in(['good', 'worn', 'damaged', 'missing'])],
            'asset_conditions.*.note' => ['nullable', 'string', 'max:500'],
            'checkout_damage_note' => ['nullable', 'string', 'max:2000'],
            'checkout_photos' => ['nullable', 'array', 'max:10'],
            'checkout_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'handover_confirmed' => ['accepted'],
        ]);
        $storedPaths = [];
        try {
            foreach ($request->file('checkout_photos', []) as $photo) {
                $storedPaths[] = $photo->store("contracts/{$contract->id}/checkout", 'local');
            }
            $data['checkout_photo_paths'] = $storedPaths;
            $contract = $this->lifecycle->checkOut($contract, $request->user(), $data);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }
        $contract->load('settlementStatement.invoice');
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_checked_out', 'Đã ghi nhận trả phòng', 'Hợp đồng '.$contract->contract_code.' đang chờ quyết toán và xử lý tiền cọc.');
        if ($contract->settlementStatement?->invoice) {
            app(ClientNotificationService::class)->invoice(
                $contract->settlementStatement->invoice,
                'settlement_invoice_issued',
                'Bảng quyết toán trả phòng đã được lập',
                'Vui lòng kiểm tra các khoản tiền phòng, điện nước và điều chỉnh cuối hợp đồng.'
            );
        }

        return redirect()->route('admin.contracts.check-out.form', $contract)
            ->with('success', 'Đã ghi nhận bàn giao và lập quyết toán. Tiếp tục xử lý các bước còn lại.');
    }

    /** Route cũ được giữ tương thích nhưng thực hiện đúng nghiệp vụ checkout mới. */
    public function end(Request $request, $id)
    {
        $request->merge([
            'actual_move_out_at' => $request->input('actual_move_out_at', $request->input('actual_end_date')),
            'checkout_reason' => $request->input('checkout_reason', trim(($request->input('termination_reason') ?? '').' '.($request->input('termination_note') ?? ''))),
            'checkout_key_count' => $request->input('checkout_key_count', 0),
            'handover_confirmed' => $request->input('handover_confirmed', $request->boolean('confirm_end') ? '1' : null),
        ]);

        return $this->checkOut($request, Contract::findOrFail($id));
    }

    public function completeSettlement(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'settlement_note' => ['nullable', 'string', 'max:2000'],
            'write_off_outstanding' => ['nullable', 'boolean'],
            'write_off_reason' => ['nullable', 'required_if:write_off_outstanding,1', 'string', 'max:2000'],
            'confirm_complete' => ['accepted'],
        ]);
        $this->lifecycle->completeSettlement($contract, $request->user(), $data);
        app(ClientNotificationService::class)->contract($contract->fresh(), 'contract_settlement_completed', 'Quyết toán hợp đồng đã hoàn tất', 'Ban quản lý đã hoàn tất quyết toán hợp đồng '.$contract->contract_code.'.');

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
            'proposed_monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'financial_override_reason' => ['nullable', 'string', 'min:3', 'max:1000'],
            'confirm_extend' => ['required', 'accepted'],
        ], [
            'confirm_extend.required' => 'Bạn phải xác nhận 2 bên đã thỏa thuận gia hạn.',
            'confirm_extend.accepted' => 'Bạn phải xác nhận 2 bên đã thỏa thuận gia hạn.',
        ]);
        $reason = $data['reason'] ?? trim(($data['extend_reason'] ?? '').' '.($data['extend_note'] ?? ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Gia hạn hợp đồng bắt buộc có lý do.'])->withInput();
        }
        $extensionRequest = $this->extendByAdminAgreement($contract, $request, $data, $reason);
        $contract = $extensionRequest->contract->fresh();
        app(ClientNotificationService::class)->contract(
            $contract,
            'extension_request_approved',
            'Hợp đồng đã được gia hạn',
            'Theo thỏa thuận giữa hai bên, hợp đồng '.$contract->contract_code.' đã được gia hạn đến '.$contract->end_date?->format('d/m/Y').'.'
        );

        return redirect()->route('admin.contracts.show', $contract)
            ->with('success', 'Đã gia hạn hợp đồng theo thỏa thuận của hai bên.');
    }

    private function extendByAdminAgreement(Contract $contract, Request $request, array $data, string $reason): ContractExtensionRequest
    {
        return DB::transaction(function () use ($contract, $request, $data, $reason): ContractExtensionRequest {
            $lockedContract = Contract::query()
                ->with(['currentMembers', 'invoices.payments', 'invoices.adjustments'])
                ->lockForUpdate()
                ->findOrFail($contract->id);

            if (! in_array($lockedContract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
                throw ValidationException::withMessages(['contract' => 'Chỉ có thể gia hạn hợp đồng đang có hiệu lực hoặc vừa hết hạn nhưng chưa trả phòng.']);
            }

            $newEndDate = Carbon::parse($data['new_end_date'])->startOfDay();
            if (! $lockedContract->end_date || ! $newEndDate->gt($lockedContract->end_date->copy()->startOfDay())) {
                throw ValidationException::withMessages(['new_end_date' => 'Ngày kết thúc mới phải sau ngày kết thúc hiện tại.']);
            }

            $existingRequest = $lockedContract->extensionRequests()->whereIn('status', [
                ContractExtensionRequest::STATUS_PENDING,
                ContractExtensionRequest::STATUS_AWAITING_CONFIRMATION,
            ])->lockForUpdate()->latest('id')->first();

            if ($lockedContract->terminationRequests()->whereIn('status', [
                ContractTerminationRequest::STATUS_PENDING,
                ContractTerminationRequest::STATUS_APPROVED,
            ])->exists()) {
                throw ValidationException::withMessages(['contract' => 'Hợp đồng đang có yêu cầu hoặc lịch trả phòng. Hãy xử lý yêu cầu đó trước khi gia hạn.']);
            }

            $outstanding = $lockedContract->invoices
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount);
            if ($outstanding > 0 && blank($data['financial_override_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'financial_override_reason' => 'Hợp đồng còn '.number_format($outstanding, 0, ',', '.').' VNĐ công nợ. Hãy thu hết hoặc nhập lý do chấp nhận ngoại lệ.',
                ]);
            }

            $monthlyRent = (float) ($data['proposed_monthly_rent'] ?? $lockedContract->monthly_rent);
            $setting = Setting::currentOrCreate();

            $extensionData = [
                'current_end_date' => $lockedContract->end_date,
                'approved_end_date' => $newEndDate,
                'proposed_monthly_rent' => $monthlyRent,
                'proposed_deposit_amount' => $lockedContract->deposit_amount,
                'status' => ContractExtensionRequest::STATUS_APPROVED,
                'financial_override_reason' => $data['financial_override_reason'] ?? null,
                'terms_snapshot' => [
                    'old_end_date' => $lockedContract->end_date?->toDateString(),
                    'new_end_date' => $newEndDate->toDateString(),
                    'old_monthly_rent' => (float) $lockedContract->monthly_rent,
                    'new_monthly_rent' => $monthlyRent,
                    'deposit_amount' => (float) $lockedContract->deposit_amount,
                    'outstanding_at_offer' => round($outstanding, 2),
                    'fees' => [
                        'electric_price' => (float) $setting->electric_price,
                        'water_price' => (float) $setting->water_price,
                        'internet_fee' => (float) $setting->internet_fee,
                        'service_fee' => (float) $setting->service_fee,
                        'parking_fee' => (float) $setting->parking_fee,
                    ],
                    'tenants' => $lockedContract->currentMembers->map(fn ($member) => [
                        'id' => $member->id,
                        'full_name' => $member->full_name,
                        'role' => $member->role,
                    ])->values()->all(),
                ],
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ];
            if ($existingRequest) {
                $extensionRequest = $existingRequest->forceFill($extensionData + [
                    'admin_note' => $reason,
                ]);
                $extensionRequest->save();
                app(AdminNotificationService::class)->resolve('extension_request', $extensionRequest);
            } else {
                $extensionRequest = $lockedContract->extensionRequests()->create($extensionData + [
                    'requested_end_date' => $newEndDate,
                    'reason' => $reason,
                ]);
            }

            $this->lifecycle->extendContract(
                $lockedContract,
                $request->user(),
                $newEndDate,
                $reason,
                [
                    'monthly_rent' => $monthlyRent,
                    'extension_request_id' => $extensionRequest->id,
                ],
            );

            return $extensionRequest->fresh('contract');
        }, 3);
    }

    public function print($id)
    {
        $contract = Contract::with([
            'room.amenities', 'tenant', 'currentMembers.tenant', 'representativeMember.tenant', 'handoverItems',
        ])->findOrFail($id);
        $referenceReading = $contract->utilityReadings()->latest('record_date')->latest('id')->first()
            ?? $contract->room?->utilityReadings()->latest('record_date')->latest('id')->first();
        $setting = Setting::currentOrCreate();

        return view('admin.contracts.print', compact('contract', 'referenceReading', 'setting'));
    }

    public function template()
    {
        $setting = Setting::currentOrCreate();

        return view('admin.contracts.template', compact('setting'));
    }

    public function templatePrint()
    {
        $setting = Setting::currentOrCreate();

        return view('admin.contracts.template-print', compact('setting'));
    }

    public function file(Contract $contract): StreamedResponse
    {
        abort_unless($contract->contractFileExists(), 404);

        return Storage::disk('local')->response($contract->contract_file);
    }

    public function checkoutPhoto(Contract $contract, int $index): StreamedResponse
    {
        Gate::authorize('manageLifecycle', $contract);
        $path = $contract->checkout_photo_paths[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function identityDocument(ContractTenant $member, string $side): StreamedResponse
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $path = $side === 'front' ? $member->identity_front_path : $member->identity_back_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $label = $side === 'front' ? 'mat-truoc' : 'mat-sau';

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';

        return Storage::disk('local')->response($path, "CCCD-{$label}-{$member->contract_id}.{$extension}");
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
        $existingRepresentative = $contract?->representativeMember()->first();
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
            'members' => collect($request->input('members', []))
                ->filter(fn ($member): bool => filled($member['full_name'] ?? null))
                ->all(),
        ]);

        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'representative.full_name' => ['required', 'string', 'max:255'],
            'representative.date_of_birth' => ['nullable', 'date', new AdultDateOfBirth],
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
            'contract_duration' => ['required', 'integer', 'min:12', 'max:120'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'scheduled_move_in_date' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:reservation_expires_at'],
            'reservation_expires_at' => ['required', 'date', 'after_or_equal:scheduled_move_in_date', 'before_or_equal:end_date'],
            'move_in_terms_confirmed' => ['exclude'],
            'deposit_amount' => ['exclude'],
            'members' => ['nullable', 'array', 'max:100'],
            'members.*.id' => ['nullable', 'integer', Rule::exists('contract_tenants', 'id')->where(
                fn ($query) => $contract ? $query->where('contract_id', $contract->id) : $query->whereRaw('1 = 0')
            )],
            'members.*.full_name' => ['required', 'string', 'max:150'],
            'members.*.date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'members.*.gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'members.*.identity_number' => ['required', 'digits:12', 'distinct'],
            'members.*.cccd_issue_date' => ['required', 'date', 'before_or_equal:today'],
            'members.*.cccd_issue_place' => ['required', 'string', 'max:255'],
            'members.*.identity_front' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'members.*.identity_back' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'members.*.phone' => ['required', 'regex:/^[0-9]{10,15}$/', 'distinct'],
            'members.*.email' => ['nullable', 'email', 'max:255', 'distinct'],
            'members.*.address' => ['required', 'string', 'max:500'],
            'service_enabled' => ['exclude'],
            'parking_enabled' => ['exclude'],
            'parking_vehicle_type' => ['exclude'],
            'parking_quantity' => ['exclude'],
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
            'move_in_terms_confirmed.accepted' => 'Quản trị viên phải xác nhận đã thống nhất lịch nhận phòng với khách.',
            'members.max' => 'Danh sách người thuê vượt quá giới hạn xử lý cho phép.',
            'members.*.full_name.required' => 'Vui lòng nhập họ và tên người thuê.',
            'members.*.full_name.max' => 'Họ và tên người thuê không được vượt quá 150 ký tự.',
            'members.*.date_of_birth.date' => 'Ngày sinh người thuê không đúng định dạng.',
            'members.*.date_of_birth.required' => 'Vui lòng nhập ngày sinh người thuê.',
            'members.*.date_of_birth.before_or_equal' => 'Ngày sinh người thuê không được ở tương lai.',
            'members.*.gender.required' => 'Vui lòng chọn giới tính người thuê.',
            'members.*.gender.in' => 'Giới tính người thuê không hợp lệ.',
            'members.*.identity_number.digits' => 'CCCD người thuê phải gồm đúng 12 chữ số.',
            'members.*.identity_number.distinct' => 'CCCD người thuê bị trùng trong danh sách.',
            'members.*.cccd_issue_date.required' => 'Vui lòng nhập ngày cấp CCCD của người thuê.',
            'members.*.cccd_issue_date.before_or_equal' => 'Ngày cấp CCCD không được ở tương lai.',
            'members.*.cccd_issue_place.required' => 'Vui lòng nhập nơi cấp CCCD của người thuê.',
            'members.*.identity_front.required' => 'Vui lòng chọn ảnh mặt trước CCCD của người thuê.',
            'members.*.identity_front.file' => 'Ảnh mặt trước CCCD tải lên không hợp lệ.',
            'members.*.identity_front.image' => 'Mặt trước CCCD phải là một tệp ảnh.',
            'members.*.identity_front.mimes' => 'Ảnh mặt trước CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'members.*.identity_front.max' => 'Ảnh mặt trước CCCD không được lớn hơn 5 MB.',
            'members.*.identity_back.required' => 'Vui lòng chọn ảnh mặt sau CCCD của người thuê.',
            'members.*.identity_back.file' => 'Ảnh mặt sau CCCD tải lên không hợp lệ.',
            'members.*.identity_back.image' => 'Mặt sau CCCD phải là một tệp ảnh.',
            'members.*.identity_back.mimes' => 'Ảnh mặt sau CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'members.*.identity_back.max' => 'Ảnh mặt sau CCCD không được lớn hơn 5 MB.',
            'members.*.phone.required' => 'Vui lòng nhập số điện thoại người thuê.',
            'members.*.phone.regex' => 'Số điện thoại người thuê phải gồm từ 10 đến 15 chữ số.',
            'members.*.phone.distinct' => 'Số điện thoại người thuê bị trùng trong danh sách.',
            'members.*.email.email' => 'Email người thuê không đúng định dạng.',
            'members.*.email.distinct' => 'Email người thuê bị trùng trong danh sách.',
            'members.*.address.required' => 'Vui lòng nhập địa chỉ thường trú của người thuê.',
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

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();
        $deadline = Carbon::parse($data['reservation_expires_at'])->endOfDay();
        $latestMoveIn = $start->copy()->addMonthNoOverflow()->endOfDay();
        if (Carbon::parse($data['scheduled_move_in_date'])->gt($latestMoveIn)) {
            throw ValidationException::withMessages([
                'scheduled_move_in_date' => 'Ngày dự kiến nhận phòng không được muộn quá 1 tháng kể từ ngày bắt đầu hợp đồng.',
            ]);
        }
        if ($deadline->gt($latestMoveIn)) {
            throw ValidationException::withMessages([
                'reservation_expires_at' => 'Hạn cuối nhận phòng không được muộn quá 1 tháng kể từ ngày bắt đầu hợp đồng.',
            ]);
        }
        $data['reservation_expires_at'] = $deadline;
        // Internet là phí bắt buộc theo phòng, không phụ thuộc số người và không có lựa chọn tắt trên hợp đồng.
        $data['internet_enabled'] = true;
        $data['service_enabled'] = true;
        $data['parking_vehicle_type'] = null;
        $data['parking_quantity'] = 0;
        $totalDays = max(1, $start->diffInDays($end));
        $data['move_in_window_ratio'] = round($start->diffInDays($deadline->copy()->startOfDay()) / $totalDays, 4);

        // Giữ nguyên key trong lúc validation để input chữ và file CCCD cùng index.
        // Chỉ chuẩn hóa thành mảng liên tục sau khi Laravel đã ghép input với files.
        $data['members'] = array_values($data['members'] ?? []);
        foreach ($data['members'] as $index => $memberData) {
            $existing = filled($memberData['id'] ?? null)
                ? ContractTenant::query()->where('contract_id', $contract?->id)->find($memberData['id'])
                : null;
            $hasStoredPair = $existing?->identity_front_path && $existing?->identity_back_path;
            $hasIdentityNumber = filled($memberData['identity_number'] ?? null);
            $identityChanged = $existing
                && (string) $existing->identity_number !== (string) ($memberData['identity_number'] ?? '');
            $hasFront = isset($memberData['identity_front']);
            $hasBack = isset($memberData['identity_back']);
            $requiresIdentityDocuments = true;
            $requiresNewPair = $requiresIdentityDocuments && (! $hasStoredPair || $identityChanged || $hasFront || $hasBack);
            $identityErrors = [];
            if (($hasFront || $hasBack) && ! $hasIdentityNumber) {
                $identityErrors["members.{$index}.identity_number"] = 'Vui lòng nhập số CCCD trước khi tải ảnh căn cước.';
            }
            if ($requiresNewPair && ! $hasFront) {
                $identityErrors["members.{$index}.identity_front"] = 'Vui lòng chọn ảnh mặt trước CCCD của người thuê.';
            }
            if ($requiresNewPair && ! $hasBack) {
                $identityErrors["members.{$index}.identity_back"] = 'Vui lòng chọn ảnh mặt sau CCCD của người thuê.';
            }
            if ($identityErrors) {
                throw ValidationException::withMessages($identityErrors);
            }
        }
        $data['number_of_people'] = count($data['members']) + 1;

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
        if (is_numeric($duration) && (int) $duration >= 12 && (int) $duration <= 120) {
            $calculated['end_date'] = $start->copy()->addMonthsNoOverflow((int) $duration)->toDateString();
            $calculated['reservation_expires_at'] = $start->copy()->addMonthNoOverflow()->toDateString();
        }
        $request->merge($calculated);
    }

    private function storeSubmittedIdentityDocuments(Contract $contract, array $data, User $actor, array &$storedPaths): void
    {
        if (isset($data['representative']['identity_front'], $data['representative']['identity_back'])) {
            $representative = $contract->members()->where('role', ContractTenant::ROLE_REPRESENTATIVE)
                ->lockForUpdate()->latest('id')->firstOrFail();
            $this->identityDocuments->storePair(
                $representative,
                $data['representative']['identity_front'],
                $data['representative']['identity_back'],
                $actor,
                $storedPaths,
            );
        }

        foreach ($data['members'] as $memberData) {
            if (! isset($memberData['identity_front'], $memberData['identity_back'])) {
                continue;
            }
            $member = $contract->members()->where('role', ContractTenant::ROLE_TENANT)
                ->current()->where('identity_number', $memberData['identity_number'])
                ->lockForUpdate()->latest('id')->firstOrFail();
            $this->identityDocuments->storePair(
                $member,
                $memberData['identity_front'],
                $memberData['identity_back'],
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
