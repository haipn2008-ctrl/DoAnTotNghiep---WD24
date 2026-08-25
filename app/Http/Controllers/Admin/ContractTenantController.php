<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTenant;
use App\Services\ContractTenantService;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use Illuminate\Http\Request;

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
}
