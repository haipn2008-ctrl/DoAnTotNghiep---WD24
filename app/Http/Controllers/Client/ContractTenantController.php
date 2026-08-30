<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Rules\AdultDateOfBirth;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractIdentityDocumentService;
use App\Services\ContractTenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContractTenantController extends Controller
{
    public function __construct(
        private readonly ContractTenantService $members,
        private readonly ContractIdentityDocumentService $identityDocuments,
    ) {}

    public function create(Request $request, int $contract)
    {
        $contract = Contract::query()
            ->managedBy($request->user())
            ->with(['room', 'currentMembers'])
            ->findOrFail($contract);
        abort_unless(in_array($contract->status, [
            Contract::STATUS_DRAFT,
            Contract::STATUS_PENDING_SIGNATURE,
            Contract::STATUS_PENDING_DEPOSIT,
            Contract::STATUS_AWAITING_MOVE_IN,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_EXPIRED,
        ], true), 409, 'Hợp đồng không còn nhận khai báo người thuê.');
        abort_unless($contract->hasRoomForMembers(), 409, 'Phòng đã đạt số người thuê tối đa.');

        return view('client.contracts.tenants.create', compact('contract'));
    }

    public function store(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'identity_number' => ['required', 'digits:12'],
            'cccd_issue_date' => ['required', 'date', 'before_or_equal:today'],
            'cccd_issue_place' => ['required', 'string', 'max:255'],
            'identity_front' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
        ], [
            'identity_number.required' => 'Vui lòng nhập số CCCD của người thuê.',
            'identity_number.required_with' => 'Vui lòng nhập số CCCD trước khi tải ảnh căn cước.',
            'identity_front.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'identity_front.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
            'identity_back.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'identity_back.required_with' => 'Vui lòng tải đủ cả hai mặt CCCD.',
            'phone.required' => 'Vui lòng nhập số điện thoại người thuê.',
            'phone.regex' => 'Số điện thoại người thuê phải gồm từ 10 đến 15 chữ số.',
            'gender.required' => 'Vui lòng chọn giới tính người thuê.',
            'cccd_issue_date.required' => 'Vui lòng nhập ngày cấp CCCD.',
            'cccd_issue_place.required' => 'Vui lòng nhập nơi cấp CCCD.',
            'email.email' => 'Email người thuê không đúng định dạng.',
            'address.required' => 'Vui lòng nhập địa chỉ thường trú.',
        ]);
        $storedPaths = [];
        try {
            DB::transaction(function () use ($contract, $request, $data, &$storedPaths): void {
                $member = $this->members->declareByTenant($contract, $request->user(), $data);
                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->identityDocuments->storePair(
                        $member,
                        $data['identity_front'],
                        $data['identity_back'],
                        $request->user(),
                        $storedPaths,
                    );
                }
                app(AdminNotificationService::class)->memberSubmitted($member);
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return redirect()->route('client.contracts.show', $contract)
            ->with('success', 'Đã gửi hồ sơ người thuê để quản lý duyệt. Người này không được cấp tài khoản riêng.');
    }

    public function edit(Request $request, Contract $contract, ContractTenant $member)
    {
        abort_unless((int) $member->contract_id === (int) $contract->id, 404);
        $contract = Contract::query()->managedBy($request->user())->with('room')->findOrFail($contract->id);
        $member->load('tenant');
        abort_unless(
            $member->role === ContractTenant::ROLE_TENANT
            && in_array($member->status, [ContractTenant::STATUS_PENDING, ContractTenant::STATUS_APPROVED], true)
            && in_array($contract->status, [Contract::STATUS_PENDING_SIGNATURE, Contract::STATUS_PENDING_DEPOSIT, Contract::STATUS_AWAITING_MOVE_IN], true),
            409,
            'Hồ sơ này không còn được chỉnh sửa trước nhận phòng.'
        );

        return view('client.contracts.tenants.edit', compact('contract', 'member'));
    }

    public function update(Request $request, Contract $contract, ContractTenant $member)
    {
        abort_unless((int) $member->contract_id === (int) $contract->id, 404);
        $contract = Contract::query()->managedBy($request->user())->findOrFail($contract->id);
        $member->load('tenant');
        $requiresIdentityPair = blank($member->identity_front_path)
            || blank($member->identity_back_path)
            || $request->input('identity_number') !== $member->identity_number;
        $imageRequirement = $requiresIdentityPair ? 'required' : 'nullable';
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'identity_number' => ['required', 'digits:12'],
            'cccd_issue_date' => ['required', 'date', 'before_or_equal:today'],
            'cccd_issue_place' => ['required', 'string', 'max:255'],
            'identity_front' => [$imageRequirement, 'required_with:identity_back', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => [$imageRequirement, 'required_with:identity_front', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'phone' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($member, $request, $data, &$storedPaths): void {
                $member = $this->members->updateBeforeMoveIn($member, $request->user(), $data);
                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->identityDocuments->storePair(
                        $member,
                        $data['identity_front'],
                        $data['identity_back'],
                        $request->user(),
                        $storedPaths,
                    );
                }
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        $currentMembers = $contract->currentMembers()->get();
        if ($this->members->incompleteMoveInProfiles($contract)->isEmpty()) {
            $notifications = app(ClientNotificationService::class);
            $notifications->resolveContract($contract, 'contract_members_profile_incomplete');
            $handoverReading = $contract->utilityReadings()->where('reading_type', 'handover')->first();
            if (! $currentMembers->contains('status', ContractTenant::STATUS_PENDING)
                && $contract->move_in_inventory_snapshotted_at
                && $handoverReading?->meterImageExists('electricity')
                && $handoverReading?->meterImageExists('water')) {
                $notifications->contractOnce(
                    $contract,
                    'move_in_details_ready',
                    'Thông tin nhận phòng cần xác nhận',
                    'Hồ sơ người nhận phòng đã đầy đủ. Vui lòng đối chiếu chỉ số, ảnh đồng hồ điện nước và tài sản để xác nhận nhận phòng.',
                );
            }
        }

        return redirect()->route('client.contracts.show', $contract)
            ->with('success', 'Đã cập nhật đầy đủ hồ sơ người thuê trước khi nhận phòng.');
    }

    public function withdraw(Request $request, Contract $contract, ContractTenant $member)
    {
        abort_unless((int) $member->contract_id === (int) $contract->id, 404);
        $this->members->withdrawByTenant($member, $request->user());
        app(AdminNotificationService::class)->resolve('member_review', $member);

        return back()->with('success', 'Đã rút người thuê trước khi nhận phòng và cập nhật lại danh sách dự kiến.');
    }
}
