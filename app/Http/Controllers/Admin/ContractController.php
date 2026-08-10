<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ContractHistoryService;
use App\Models\User;
use App\Services\TenantAccountLifecycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
     public function index(Request $request)
    {
        $query = Contract::with(['room', 'tenant']);

        // Tìm kiếm
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
            'full_name as name',
            'date_of_birth',
            'address',
            'cccd',
            'cccd_issue_date',
            'cccd_issue_place',
            'phone'
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

        $tenants = Tenant::select('id', 'full_name as name')
            ->whereHas('user', fn ($query) => $query->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE]))
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', ['pending', 'active']))
            ->orderBy('full_name')
            ->get();

        return redirect()
        ->route('admin.contracts.index');
    }
    /**
     * Lưu hợp đồng
     */

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'number_of_people' => ['nullable', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string'],
            'contract_content' => ['nullable', 'string'],
            'confirm_contract_accuracy' => ['required', 'accepted'],
        ]);

        $contract = DB::transaction(function () use ($data) {
            $room = Room::lockForUpdate()->findOrFail($data['room_id']);
            $tenant = Tenant::with('user')->lockForUpdate()->findOrFail($data['tenant_id']);

            if (
                $room->status !== Room::STATUS_AVAILABLE ||
                $room->contracts()->whereIn('status', [
                    Contract::STATUS_DRAFT,
                    Contract::STATUS_PENDING_SIGNATURE,
                    Contract::STATUS_SIGNED,
                    Contract::STATUS_DEPOSIT_PAID,
                    Contract::STATUS_ACTIVE,
                ])->exists()
            ) {
                throw ValidationException::withMessages([
                    'room_id' => 'Phòng đang có người thuê hoặc không sẵn sàng cho thuê.',
                ]);
            }

            $numberOfPeople = $data['number_of_people'] ?? 1;
            $maxPeople = (int) ($room->max_people ?? 1);

            if ($numberOfPeople > $maxPeople) {
                throw ValidationException::withMessages([
                    'number_of_people' => 'Số người không được vượt quá sức chứa của phòng.',
                ]);
            }

            if (
                !$tenant->user ||
                !in_array($tenant->user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE], true)
            ) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Tài khoản khách thuê không còn hợp lệ. Hãy tạo hồ sơ và tài khoản mới cho khách mới.',
                ]);
            }

            if ($tenant->contracts()->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_SIGNED,
                Contract::STATUS_DEPOSIT_PAID,
                Contract::STATUS_ACTIVE,
            ])->exists()) {
                throw ValidationException::withMessages([
                    'tenant_id' => 'Khách thuê đã có hợp đồng đang hoạt động hoặc đang trong quy trình ký.',
                ]);
            }

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $createdDate = now();
            $content = $data['contract_content'] ?? '';

            $content = strtr($content, [
                '{{created_day}}' => $createdDate->format('d'),
                '{{created_month}}' => $createdDate->format('m'),
                '{{created_year}}' => $createdDate->format('Y'),
                '{{house_address}}' => 'Cầu Giấy - Hà Nội',
                '{{tenant_name}}' => $tenant->full_name ?? '',
                '{{tenant_dob}}' => $tenant->date_of_birth
                    ? Carbon::parse($tenant->date_of_birth)->format('d/m/Y') : '',
                '{{tenant_address}}' => $tenant->address ?? '',
                '{{tenant_cccd}}' => $tenant->cccd ?? '',
                '{{tenant_cccd_issue_date}}' => $tenant->cccd_issue_date
                    ? Carbon::parse($tenant->cccd_issue_date)->format('d/m/Y') : '',
                '{{tenant_cccd_issue_place}}' => $tenant->cccd_issue_place ?? '',
                '{{tenant_phone}}' => $tenant->phone ?? '',
                '{{room}}' => $room->room_code ?? '',
                '{{price}}' => number_format((float) $room->price, 0, ',', '.'),
                '{{deposit}}' => number_format((float) ($data['deposit_amount'] ?? 0), 0, ',', '.'),
                '{{start_day}}' => $startDate->format('d'),
                '{{start_month}}' => $startDate->format('m'),
                '{{start_year}}' => $startDate->format('Y'),
                '{{end_day}}' => $endDate->format('d'),
                '{{end_month}}' => $endDate->format('m'),
                '{{end_year}}' => $endDate->format('Y'),
            ]);

            $contract = Contract::create([
                'contract_code' => 'TMP-' . Str::uuid(),
                'room_id' => $room->id,
                'tenant_id' => $tenant->id,
                'representative_tenant_id' => $tenant->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'monthly_rent' => $room->price,
                'deposit_amount' => $data['deposit_amount'] ?? 0,
                'number_of_people' => $numberOfPeople,
                'contract_content' => $content,
                'note' => $data['note'] ?? null,
                'status' => Contract::STATUS_DRAFT,
                'deposit_status' => Contract::DEPOSIT_PENDING,
            ]);

            $contract->update([
                'contract_code' => 'HD' . str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT),
            ]);

            // Giữ phòng ở trạng thái đã được giữ cho hợp đồng mới.
            $room->update([
                'status' => Room::STATUS_OCCUPIED,
                'current_people' => $numberOfPeople,
            ]);

            return $contract;
        });

        ContractHistoryService::created($contract);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Đang chờ khách thuê ký hợp đồng.');

        $room->update([
            'status' => Room::STATUS_OCCUPIED,
            'current_people' => 1,
        ]);
        ContractHistoryService::created($contract);

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', 'Tạo hợp đồng thành công. Đang chờ khách thuê ký hợp đồng.');
    }
    /**
     * Chi tiết hợp đồng
     */
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
                ->route('admin.contracts.index', $contract)
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
                ->route('admin.contracts.index', $contract)
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
            ContractHistoryService::log(
                $contract,
                ContractHistoryService::UPDATED,
                'Admin đã chỉnh sửa thông tin hợp đồng.',
                $request->reason,
                $oldChanged,
                $newChanged
            );

        });

        return redirect()
            ->route('admin.contracts.index', $contract)
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
            'actual_end_date' => ['required', 'date'],
            'termination_reason' => 'required|string|max:255',
            'termination_note' => 'nullable|string',
            'confirm_end' => 'nullable|accepted',
        ]);

        $actualEndDate = Carbon::parse($request->actual_end_date)->startOfDay();

        if (
            $actualEndDate->lt($contract->start_date->startOfDay()) ||
            $actualEndDate->isFuture()
        ) {
            return back()
                ->withInput()
                ->with('error', 'Ngày trả phòng phải từ ngày bắt đầu hợp đồng đến ngày hiện tại.');
        }
        $oldStatus = $contract->status;

        $oldRoomStatus = $contract->room->status;

        $accountStatus = DB::transaction(function () use (
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
            ContractHistoryService::log(
                $contract,
                ContractHistoryService::TERMINATED,
                'Hợp đồng đã được kết thúc.',
                $request->termination_reason,
                [
                    'status' => $oldStatus,
                    'room_status' => $oldRoomStatus,
                    'end_date' => optional($contract->end_date)->format('Y-m-d'),
                ],
                [
                    'status' => Contract::STATUS_TERMINATED,
                    'room_status' => Room::STATUS_AVAILABLE,
                    'actual_end_date' => $request->actual_end_date,
                ]
            );

            return app(TenantAccountLifecycle::class)->sync($contract->tenant);
        });

        $message = match ($accountStatus) {
            User::STATUS_SETTLING => 'Kết thúc hợp đồng thành công. Tài khoản khách được giữ ở chế độ quyết toán do còn công nợ.',
            User::STATUS_ACTIVE, User::STATUS_PENDING => 'Kết thúc hợp đồng thành công. Tài khoản khách vẫn được giữ do còn hợp đồng khác.',
            default => 'Kết thúc hợp đồng thành công. Tài khoản khách đã ngừng hoạt động.',
        };

        return redirect()
            ->route('admin.contracts.index')
            ->with('success', $message);
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

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::EXTENDED,
                'Hợp đồng đã được gia hạn.',
                $request->extend_reason,
                [
                    'end_date' => optional($oldEndDate)->format('Y-m-d'),
                ],
                [
                    'end_date' => $request->new_end_date,
                ]
            );

        });

        return back()->with(
            'success',
            'Gia hạn hợp đồng thành công.'
        );
    }

    /**
     * Cập nhật thông tin người thuê.
     *
     * Tách riêng khỏi update() để không xung đột với chức năng
     * chỉnh sửa thông tin hợp đồng của luồng hiện tại.
     */
    public function updateTenant(Request $request, Contract $contract)
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
            ->route('admin.contracts.index')
            ->with('success', 'Cập nhật thông tin người thuê thành công.');
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

        ContractHistoryService::log(
            $contract,
            ContractHistoryService::SENT_FOR_SIGNATURE,
            'Admin đã gửi hợp đồng cho khách thuê ký.',
            null,
            [
                'status' => Contract::STATUS_DRAFT,
            ],
            [
                'status' => Contract::STATUS_PENDING_SIGNATURE,
            ]
        );

        return back()->with(
            'success',
            'Đã gửi hợp đồng cho khách thuê ký.'
        );
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

        abort_unless(
            $contract->status === Contract::STATUS_ACTIVE,
            409,
            'Chỉ hợp đồng đang hiệu lực mới có thể kết thúc.'
        );

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

        abort_unless(
            $contract->status === Contract::STATUS_ACTIVE,
            409,
            'Chỉ hợp đồng đang hiệu lực mới có thể gia hạn.'
        );

        return view('admin.contracts.extend-form', compact('contract'));
    }

    public function recallSignature(Request $request, Contract $contract)
    {
        if (!$contract->isPendingSignature()) {
            return back()->with(
                'error',
                'Chỉ hợp đồng đang chờ ký mới được thu hồi.'
            );
        }

        $request->validate([
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ], [
            'reason.required' => 'Vui lòng nhập lý do thu hồi hợp đồng.',
            'reason.min'      => 'Lý do thu hồi phải có ít nhất 5 ký tự.',
            'reason.max'      => 'Lý do thu hồi không được vượt quá 500 ký tự.',
        ]);

        DB::transaction(function () use ($contract, $request) {

            $oldStatus = $contract->status;

            $contract->update([
                'status' => Contract::STATUS_DRAFT,
            ]);

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::RECALLED,
                'Admin đã thu hồi hợp đồng để chỉnh sửa.',
                $request->reason,
                [
                    'status' => $oldStatus,
                ],
                [
                    'status' => Contract::STATUS_DRAFT,
                ]
            );
        });

        return back()->with(
            'success',
            'Đã thu hồi hợp đồng. Hợp đồng được chuyển về bản nháp để chỉnh sửa.'
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

        // Lưu trạng thái cũ trước khi cập nhật
        $oldStatus = $contract->status;

        DB::transaction(function () use ($contract, $oldStatus) {

            // Chuyển hợp đồng sang đã ký
            $contract->update([
                'status' => Contract::STATUS_SIGNED,
                'signed_at' => now(),
            ]);

            // Ghi lịch sử
            ContractHistoryService::log(
                $contract,
                ContractHistoryService::SIGNED,
                'Khách thuê đã ký hợp đồng.',
                null,
                [
                    'status' => $oldStatus,
                ],
                [
                    'status' => Contract::STATUS_SIGNED,
                ]
            );
        });

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
                'Chỉ hợp đồng đã ký mới có thể xác nhận tiền cọc.'
            );
        }

        // Lưu dữ liệu cũ
        $oldStatus = $contract->status;
        $oldDepositStatus = $contract->deposit_status;

        DB::transaction(function () use (
            $contract,
            $oldStatus,
            $oldDepositStatus
        ) {

            // Xác nhận đã đóng cọc
            $contract->update([
                'status' => Contract::STATUS_DEPOSIT_PAID,
                'deposit_status' => Contract::DEPOSIT_PAID,
            ]);

            // Ghi lịch sử hợp đồng
            ContractHistoryService::log(
                $contract,
                ContractHistoryService::DEPOSIT_PAID,
                'Admin đã xác nhận khách thuê đóng tiền cọc.',
                null,
                [
                    'status' => $oldStatus,
                    'deposit_status' => $oldDepositStatus,
                ],
                [
                    'status' => Contract::STATUS_DEPOSIT_PAID,
                    'deposit_status' => Contract::DEPOSIT_PAID,
                ]
            );
        });

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
                'Tiền cọc của hợp đồng này không thể xử lý hoặc đã được xử lý.'
            );
        }

        $request->validate([
            'deposit_process_type' => [
                'required',
                Rule::in([
                    'full_refund',
                    'partial_refund',
                    'no_refund'
                ])
            ],

            'deduction_amount' => 'nullable|numeric|min:0',
            'return_reason'    => 'required|string|max:255',
            'return_note'      => 'nullable|string|max:1000',
        ]);

        $deposit = (float) $contract->deposit_amount;
        $type = $request->deposit_process_type;


        /*
        |--------------------------------------------------------------------------
        | Xác định hình thức xử lý tiền cọc
        |--------------------------------------------------------------------------
        */

        if ($type === 'full_refund') {

            // Hoàn toàn bộ
            $deduction = 0;
            $refund = $deposit;

            $depositStatus = Contract::DEPOSIT_RETURNED;

            $description = 'Hoàn toàn bộ tiền cọc';

        } elseif ($type === 'partial_refund') {

            // Hoàn một phần
            $deduction = (float) $request->deduction_amount;

            if ($deduction <= 0 || $deduction >= $deposit) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Khấu trừ một phần phải lớn hơn 0 và nhỏ hơn tiền cọc.'
                    );
            }

            $refund = $deposit - $deduction;

            $depositStatus = Contract::DEPOSIT_PARTIAL;

            $description = 'Khấu trừ một phần tiền cọc';

        } else {

            // Không hoàn
            $deduction = $deposit;
            $refund = 0;

            $depositStatus = Contract::DEPOSIT_FORFEITED;

            $description = 'Không hoàn tiền cọc';
        }


        /*
        |--------------------------------------------------------------------------
        | Cập nhật hợp đồng + ghi lịch sử
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $contract,
            $request,
            $type,
            $depositStatus,
            $deduction,
            $refund,
            $description,
            $deposit
        ) {

            // Dữ liệu trước khi xử lý
            $oldStatus = $contract->status;
            $oldDepositStatus = $contract->deposit_status;


            // Cập nhật hợp đồng
            $contract->update([

                'status' => Contract::STATUS_COMPLETED,

                'deposit_status' => $depositStatus,

                'deposit_process_type' => $type,

                'deposit_refund_amount' => $refund,

                'deposit_deduction_amount' => $deduction,

                'deposit_processed_at' => now(),

                'deposit_process_reason' => $request->return_reason,

                'deposit_process_note' => $request->return_note,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Ghi lịch sử hợp đồng
            |--------------------------------------------------------------------------
            */

            ContractHistoryService::log(
                $contract,

                ContractHistoryService::DEPOSIT_PROCESSED,

                $description,

                $request->return_reason,

                // Dữ liệu cũ
                [
                    'status' => $oldStatus,

                    'deposit_status' => $oldDepositStatus,

                    'deposit_amount' => $deposit,
                ],

                // Dữ liệu mới
                [
                    'status' => Contract::STATUS_COMPLETED,

                    'deposit_status' => $depositStatus,

                    'process_type' => $type,

                    'deposit_amount' => $deposit,

                    'refund_amount' => $refund,

                    'deduction_amount' => $deduction,

                    'note' => $request->return_note,
                ]
            );
        });


        return back()->with(
            'success',
            'Đã xử lý tiền cọc và hoàn tất hợp đồng.'
        );
    }
    public function activate(Contract $contract)
    {
        if (!$contract->canActivate()) {
            return back()->with(
                'error',
                'Hợp đồng chưa đủ điều kiện để kích hoạt.'
            );
        }

        DB::transaction(function () use ($contract) {

            // Lưu trạng thái cũ
            $oldStatus = $contract->status;

            // Kích hoạt hợp đồng
            $contract->update([
                'status' => Contract::STATUS_ACTIVE,
            ]);

            // Ghi lịch sử hợp đồng
            ContractHistoryService::log(
                $contract,

                ContractHistoryService::ACTIVATED,

                'Hợp đồng đã được kích hoạt và bắt đầu có hiệu lực.',

                null,

                // Dữ liệu cũ
                [
                    'status' => $oldStatus,
                ],

                // Dữ liệu mới
                [
                    'status' => Contract::STATUS_ACTIVE,
                ]
            );
        });

        return back()->with(
            'success',
            'Hợp đồng đã được kích hoạt.'
        );
    }
    public function destroy(Contract $contract)
    {
        $room = $contract->room;
        if ($contract->invoices()->exists()) {
            return back()->with('error', 'Không thể xóa hợp đồng đã phát sinh hóa đơn.');
        }

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
    


    private function contractQuery(Request $request)
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in([
                    Contract::STATUS_DRAFT,
                    Contract::STATUS_PENDING_SIGNATURE,
                    Contract::STATUS_SIGNED,
                    Contract::STATUS_DEPOSIT_PAID,
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_EXPIRED,
                    Contract::STATUS_TERMINATED,
                ]),
            ],
        ]);

        $query = Contract::with(['room', 'tenant']);

        if (!empty($filters['keyword'])) {
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

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    private function activeContractQuery(Request $request)
    {
        return $this->contractQuery($request)
            ->where('status', Contract::STATUS_ACTIVE);
    }

    private function nextContractCode(): string
    {
        $lastId = (int) Contract::max('id');

        do {
            $lastId++;
            $code = 'HD' . str_pad((string) $lastId, 3, '0', STR_PAD_LEFT);
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
            'number_of_people.max' => 'Số người không được vượt quá 20.',
            'full_name.required' => 'Vui lòng nhập họ tên người thuê.',
            'cccd.required' => 'Vui lòng nhập CCCD.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.email' => 'Email không hợp lệ.',
            'actual_end_date.required' => 'Vui lòng nhập ngày trả phòng thực tế.',
            'actual_end_date.date' => 'Ngày trả phòng thực tế không hợp lệ.',
            'new_end_date.required' => 'Vui lòng nhập ngày kết thúc mới.',
            'new_end_date.date' => 'Ngày kết thúc mới không hợp lệ.',
            'extend_reason.required' => 'Vui lòng nhập lý do gia hạn.',
        ];
    }
}