<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractRepresentativeTransfer;
use App\Models\ContractTenant;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractTenantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ContractTenantController extends Controller
{
    public function __construct(private readonly ContractTenantService $members) {}

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
