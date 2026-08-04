<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    /**
     * Hợp đồng của tôi
     */
    public function index()
    {
        $user = Auth::user();

        // Chỉ khách thuê được truy cập
        if ($user->role_id !== 2) {
            return redirect()->route('dashboard');
        }

        $tenant = $user->tenant;

        // Tài khoản chưa được liên kết khách thuê
        if (!$tenant) {
            return view('client.contracts.index', [
                'tenant' => null,
                'contracts' => collect(),
            ]);
        }

        // Lấy TẤT CẢ hợp đồng
        // Hợp đồng mới nhất nằm trên cùng
        $contracts = Contract::with(['room', 'tenant'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_SIGNED,
                Contract::STATUS_DEPOSIT_PAID,
                Contract::STATUS_ACTIVE,
                Contract::STATUS_EXPIRED,
                Contract::STATUS_TERMINATED,
                Contract::STATUS_DEPOSIT_RETURNED,
            ])
            ->latest('id')
            ->get();

        return view('client.contracts.index', compact(
            'tenant',
            'contracts'
        ));
    }


    /**
     * Xem chi tiết hợp đồng
     */
    public function show(Contract $contract)
    {
        $user = Auth::user();

        if ($user->role_id !== 2) {
            return redirect()->route('dashboard');
        }

        $tenant = $user->tenant;

        /*
        * QUAN TRỌNG:
        * Không cho khách A xem hợp đồng khách B
        * bằng cách nhập ID trên URL.
        */
        if (!$tenant || $contract->tenant_id !== $tenant->id) {
            abort(403, 'Bạn không có quyền xem hợp đồng này.');
        }

        /*
        * Load dữ liệu hợp đồng
        * - room: phòng
        * - tenant: khách thuê
        * - histories.user: lịch sử + người thực hiện
        */
        $contract->load([
            'room',
            'tenant',
            'histories.user',
        ]);

        return view(
            'client.contracts.show',
            compact('contract')
        );
    }

    /**
     * In hợp đồng của khách thuê
     */
    public function print(Contract $contract)
    {
        $user = Auth::user();

        // Chỉ khách thuê
        if ($user->role_id !== 2) {
            abort(403);
        }

        $tenant = $user->tenant;

        // Không cho khách xem/in hợp đồng của người khác
        if (!$tenant || $contract->tenant_id !== $tenant->id) {
            abort(403, 'Bạn không có quyền in hợp đồng này.');
        }

        $contract->load([
            'room',
            'tenant',
            'representative'
        ]);

        return view('client.contracts.print', compact('contract'));
    }

    /**
     * Tải hợp đồng PDF
     */
    public function download(Contract $contract)
    {
        $user = Auth::user();

        // Chỉ khách thuê
        if ($user->role_id !== 2) {
            abort(403);
        }

        $tenant = $user->tenant;

        // Không cho tải hợp đồng của khách khác
        if (!$tenant || $contract->tenant_id !== $tenant->id) {
            abort(403, 'Bạn không có quyền tải hợp đồng này.');
        }

        $contract->load([
            'room',
            'tenant',
            'representative'
        ]);

        $pdf = Pdf::loadView('client.contracts.pdf', [
            'contract' => $contract
        ]);

        $pdf->setPaper('a4', 'portrait');

        $fileName = 'hop-dong-' . $contract->contract_code . '.pdf';

        return $pdf->download($fileName);
    }

    public function sign(\Illuminate\Http\Request $request, Contract $contract)
    {
        $user = Auth::user();

        if ($user->role_id !== 2) {
            abort(403);
        }

        $tenant = $user->tenant;

        // Không cho ký hợp đồng của người khác
        if (!$tenant || $contract->tenant_id !== $tenant->id) {
            abort(403, 'Bạn không có quyền ký hợp đồng này.');
        }

        // Chỉ được ký khi Admin đã gửi
        if (!$contract->isPendingSignature()) {
            return back()->with(
                'error',
                'Hợp đồng này hiện không ở trạng thái chờ ký.'
            );
        }

        $request->validate([
            'signature' => ['required', 'string'],
        ]);

        $signature = $request->signature;

        // Kiểm tra đúng ảnh PNG base64 từ canvas
        if (!preg_match('/^data:image\/png;base64,/', $signature)) {
            return back()->with(
                'error',
                'Chữ ký không hợp lệ.'
            );
        }

        // Bỏ phần data:image/png;base64,
        $imageData = preg_replace(
            '/^data:image\/png;base64,/',
            '',
            $signature
        );

        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            return back()->with(
                'error',
                'Không thể xử lý chữ ký.'
            );
        }

        // Tên file
        $fileName =
            'contract_' .
            $contract->id .
            '_tenant_' .
            time() .
            '.png';

        $path = 'signatures/contracts/' . $fileName;

        // Lưu vào storage/app/public
        \Illuminate\Support\Facades\Storage::disk('public')
            ->put($path, $imageData);

        // Cập nhật hợp đồng
        $contract->update([
            'tenant_signature' => $path,
            'status' => Contract::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        return redirect()
            ->route('client.contracts.show', $contract)
            ->with('success', 'Bạn đã ký hợp đồng thành công.');
    }
}