<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Services\ContractHistoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractExtensionRequestController extends Controller
{
    /**
     * Trang yêu cầu gia hạn của khách thuê
     */
    public function index()
    {
        $user = Auth::user();

        // Lấy các hợp đồng của khách thuê hiện tại
        $contracts = Contract::with('room')
            ->whereHas('tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', Contract::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->get();

        // Các yêu cầu đã gửi
        $extensionRequests = ContractExtensionRequest::with(['contract.room'])
            ->whereHas('contract.tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        return view('client.contracts.extension.index', compact(
            'contracts',
            'extensionRequests'
        ));
    }

    /**
     * Gửi yêu cầu gia hạn
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'requested_end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'contract_id.required' => 'Vui lòng chọn hợp đồng.',
            'requested_end_date.required' => 'Vui lòng chọn ngày muốn gia hạn.',
            'requested_end_date.date' => 'Ngày gia hạn không hợp lệ.',
            'reason.max' => 'Lý do không được vượt quá 1000 ký tự.',
        ]);

        // Quan trọng: không được lấy contract bất kỳ bằng ID.
        // Contract phải thuộc chính khách đang đăng nhập.
        $contract = Contract::where('id', $validated['contract_id'])
            ->whereHas('tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', Contract::STATUS_ACTIVE)
            ->firstOrFail();

        // Ngày mới phải sau ngày kết thúc hiện tại
        if (
            !$contract->end_date ||
            $validated['requested_end_date'] <= $contract->end_date->format('Y-m-d')
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'requested_end_date' =>
                        'Ngày gia hạn mới phải sau ngày kết thúc hiện tại của hợp đồng.'
                ]);
        }

        // Không cho spam nhiều yêu cầu đang chờ cho cùng hợp đồng
        $hasPendingRequest = ContractExtensionRequest::where(
                'contract_id',
                $contract->id
            )
            ->where(
                'status',
                ContractExtensionRequest::STATUS_PENDING
            )
            ->exists();

        if ($hasPendingRequest) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                        'Hợp đồng này đang có một yêu cầu gia hạn chờ xử lý.'
                ]);
        }

        DB::transaction(function () use ($contract, $validated) {

            ContractExtensionRequest::create([
                'contract_id'        => $contract->id,
                'current_end_date'   => $contract->end_date,
                'requested_end_date' => $validated['requested_end_date'],
                'reason'             => $validated['reason'] ?? null,
                'status'             => ContractExtensionRequest::STATUS_PENDING,
            ]);

            ContractHistoryService::log(
                $contract,
                ContractHistoryService::EXTENSION_REQUESTED,
                'Khách thuê đã gửi yêu cầu gia hạn hợp đồng.',
                $validated['reason'] ?? 'Không có lý do.',
                [
                    'end_date' => $contract->end_date?->format('Y-m-d'),
                    'request_status' => null,
                ],
                [
                    'end_date' => $validated['requested_end_date'],
                    'request_status' => ContractExtensionRequest::STATUS_PENDING,
                ]
            );
        });

        return redirect()
            ->route('client.extension-requests.index')
            ->with('success', 'Gửi yêu cầu gia hạn hợp đồng thành công.');
    }
}