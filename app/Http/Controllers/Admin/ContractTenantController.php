<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractRepresentativeTransfer;
use App\Models\ContractTenant;
use App\Models\Setting;
use App\Models\Tenant;
use App\Rules\AdultDateOfBirth;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractIdentityDocumentService;
use App\Services\ContractTenantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ContractTenantController extends Controller
{
    public function __construct(
        private readonly ContractTenantService $members,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function create(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        abort_unless(in_array($contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true), 409, 'Hợp đồng không còn nhận thêm người thuê.');
        abort_unless($contract->actual_move_in_at, 409, 'Hợp đồng chưa hoàn tất nhận phòng.');

        $contract->load(['room', 'currentMembers']);
        abort_if($contract->currentMembers->count() >= (int) $contract->room->max_people, 409, 'Phòng đã đạt số người tối đa.');

        $availableTenants = Tenant::query()
            ->active()
            ->whereNotNull('full_name')
            ->whereNotNull('date_of_birth')
            ->whereDate('date_of_birth', '<=', today()->subYears(18))
            ->whereNotNull('gender')
            ->whereNotNull('cccd')
            ->whereNotNull('cccd_issue_date')
            ->whereNotNull('cccd_issue_place')
            ->whereNotNull('phone')
            ->whereNotNull('address')
            ->whereDoesntHave('contractMemberships', fn ($query) => $query->current())
            ->whereDoesntHave('contracts', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))
            ->with(['document', 'user:id,status'])
            ->orderBy('full_name')
            ->get();

        return view('admin.contracts.tenants.create', compact('contract', 'availableTenants'));
    }

    public function store(Request $request, Contract $contract)
    {
        Gate::authorize('manageLifecycle', $contract);
        $data = $request->validate([
            'source' => ['required', Rule::in(['new', 'existing'])],
            'actual_move_in_at' => ['required', 'date', 'before_or_equal:now'],
            'tenant_id' => ['nullable', 'required_if:source,existing', 'integer', 'exists:tenants,id'],
            'full_name' => ['nullable', 'required_if:source,new', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'required_if:source,new', 'date', new AdultDateOfBirth],
            'gender' => ['nullable', 'required_if:source,new', Rule::in(['male', 'female', 'other'])],
            'identity_number' => ['nullable', 'required_if:source,new', 'digits:12'],
            'cccd_issue_date' => ['nullable', 'required_if:source,new', 'date', 'before_or_equal:today'],
            'cccd_issue_place' => ['nullable', 'required_if:source,new', 'string', 'max:255'],
            'phone' => ['nullable', 'required_if:source,new', 'regex:/^[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'required_if:source,new', 'string', 'max:500'],
            'identity_front' => ['nullable', 'required_if:source,new', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => ['nullable', 'required_if:source,new', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $existingTenant = null;
        if ($data['source'] === 'existing') {
            $existingTenant = Tenant::query()->with('document')->findOrFail($data['tenant_id']);
            if (! $existingTenant->document?->hasCompleteImagePair()) {
                $request->validate([
                    'identity_front' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                    'identity_back' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ], [
                    'identity_front.required' => 'Hồ sơ đã chọn chưa có ảnh CCCD mặt trước. Vui lòng tải ảnh lên.',
                    'identity_back.required' => 'Hồ sơ đã chọn chưa có ảnh CCCD mặt sau. Vui lòng tải ảnh lên.',
                ]);
            }
        }

        $storedPaths = [];
        try {
            $member = DB::transaction(function () use ($contract, $request, $data, $existingTenant, &$storedPaths): ContractTenant {
                $member = $this->members->addByAdmin(
                    $contract,
                    $request->user(),
                    $data['actual_move_in_at'],
                    $existingTenant,
                    $data['source'] === 'new' ? $data : [],
                );

                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->identityDocuments->storePair(
                        $member,
                        $data['identity_front'],
                        $data['identity_back'],
                        $request->user(),
                        $storedPaths,
                    );
                } elseif (! $this->identityDocuments->useTenantProfile($member, $request->user())) {
                    throw ValidationException::withMessages([
                        'identity_front' => 'Hồ sơ khách thuê chưa có đủ ảnh CCCD.',
                    ]);
                }

                return $member;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        app(ClientNotificationService::class)->contract(
            $contract->fresh(),
            'contract_member_updated',
            'Phòng có người thuê mới',
            $member->full_name.' đã được quản lý ghi nhận vào ở từ '.$member->actual_move_in_at->format('d/m/Y H:i').'.',
        );

        $moveInAt = $member->actual_move_in_at;

        return redirect()->route('admin.utilities.create', [
            'mode' => 'checkpoint',
            'month' => $moveInAt->month,
            'year' => $moveInAt->year,
            'reading_date' => $moveInAt->toDateString(),
            'room_id' => $contract->room_id,
        ])->with('success', 'Đã thêm '.$member->full_name.' vào phòng. Hãy ghi mốc điện nước tại ngày vào ở để đối chiếu.');
    }

    public function approve(Request $request, ContractTenant $member)
    {
        $this->members->approve($member, $request->user());
        app(AdminNotificationService::class)->resolve('member_review', $member);
        app(ClientNotificationService::class)->member($member, 'Hồ sơ người thuê đã được duyệt', $member->full_name.' đã được duyệt vào danh sách người thuê trong phòng. Người này không được cấp tài khoản riêng.');

        return back()->with('success', 'Đã duyệt người thuê.');
    }

    public function reject(Request $request, ContractTenant $member)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $this->members->reject($member, $request->user(), $data['reason']);
        app(AdminNotificationService::class)->resolve('member_review', $member);
        app(ClientNotificationService::class)->member($member, 'Hồ sơ người thuê bị từ chối', $member->full_name.' chưa được duyệt vào danh sách người thuê. Lý do: '.$data['reason']);

        return back()->with('success', 'Đã từ chối khai báo người thuê.');
    }

    public function moveOut(Request $request, ContractTenant $member)
    {
        $data = $request->validate([
            'actual_move_out_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->members->moveOut($member, $request->user(), $data['actual_move_out_at'], $data['reason']);
        app(ClientNotificationService::class)->member($member, 'Đã ghi nhận người thuê rời phòng', $member->full_name.' đã được cập nhật rời phòng.');

        return back()->with('success', 'Đã ghi nhận người thuê rời phòng và cập nhật số người hiện tại.');
    }

    public function restoreMoveOut(Request $request, ContractTenant $member)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->members->restoreMoveOut($member, $request->user(), $data['reason']);
        app(ClientNotificationService::class)->member(
            $member,
            'Đã khôi phục người thuê vào phòng',
            $member->full_name.' đã được khôi phục do lần ghi nhận rời phòng trước đó bị nhầm.',
        );

        return back()->with('success', 'Đã hoàn tác lần rời phòng, khôi phục số người và dữ liệu tạm trú liên quan.');
    }

    public function transferRepresentative(Request $request, ContractTenant $member)
    {
        $data = $request->validate([
            'successor_member_id' => ['required', 'integer', 'exists:contract_tenants,id'],
            'effective_at' => ['required', 'date', 'before_or_equal:now'],
            'reason' => ['required', 'string', 'max:1000'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'temporary_password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $successor = ContractTenant::query()->findOrFail($data['successor_member_id']);
        $transfer = $this->members->transferRepresentative(
            $member,
            $successor,
            $request->user(),
            $data['effective_at'],
            $data['reason'],
            $data['email'],
            $data['temporary_password'],
        );

        return back()->with('success', 'Đã chuyển người đại diện, cấp tài khoản mới và vô hiệu hóa tài khoản cũ.')
            ->with('representative_transfer_id', $transfer->id);
    }

    public function transferAppendix(ContractRepresentativeTransfer $transfer)
    {
        $transfer->load(['contract.room', 'oldTenant', 'newTenant', 'performer']);
        $setting = Setting::currentOrCreate();

        return Pdf::loadView('admin.contracts.representative-transfer-appendix', compact('transfer', 'setting'))
            ->setPaper('a4')
            ->stream('phu-luc-chuyen-giao-'.$transfer->contract->contract_code.'-'.$transfer->id.'.pdf');
    }
}
