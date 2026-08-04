<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTerminationRequest;
use App\Services\ContractHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractTerminationRequestController extends Controller
{
    /**
     * Trang yêu cầu trả phòng của khách thuê
     */
    public function index()
    {
        $user = Auth::user();

        // Các hợp đồng đang hoạt động của khách thuê hiện tại
        $contracts = Contract::with('room')
            ->whereHas('tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', Contract::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->get();

        // Lịch sử yêu cầu trả phòng đã gửi
        $terminationRequests = ContractTerminationRequest::with([
            'contract.room'
        ])
            ->whereHas('contract.tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->get();

        return view(
            'client.contracts.termination.index',
            compact('contracts', 'terminationRequests')
        );
    }

    /**
     * Gửi yêu cầu trả phòng
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'contract_id' => [
                'required',
                'integer',
                'exists:contracts,id'
            ],

            'requested_end_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],

            'reason' => [
                'required',
                'string',
                'max:1000'
            ],
        ], [
            'contract_id.required' =>
                'Vui lòng chọn hợp đồng.',

            'contract_id.exists' =>
                'Hợp đồng không tồn tại.',

            'requested_end_date.required' =>
                'Vui lòng chọn ngày dự kiến trả phòng.',

            'requested_end_date.date' =>
                'Ngày trả phòng không hợp lệ.',

            'requested_end_date.after_or_equal' =>
                'Ngày trả phòng không được nhỏ hơn ngày hiện tại.',

            'reason.required' =>
                'Vui lòng nhập lý do trả phòng.',

            'reason.max' =>
                'Lý do không được vượt quá 1000 ký tự.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra hợp đồng
        |--------------------------------------------------------------------------
        | Không được lấy một contract bất kỳ theo ID.
        | Hợp đồng phải:
        | - Thuộc khách đang đăng nhập
        | - Đang hoạt động
        */
        $contract = Contract::where(
                'id',
                $validated['contract_id']
            )
            ->whereHas('tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', Contract::STATUS_ACTIVE)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra yêu cầu đang chờ
        |--------------------------------------------------------------------------
        | Một hợp đồng không được có nhiều yêu cầu trả phòng pending.
        */
        $hasPendingRequest = ContractTerminationRequest::where(
                'contract_id',
                $contract->id
            )
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRequest) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract_id' =>
                        'Hợp đồng này đang có một yêu cầu trả phòng chờ xử lý.'
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Tạo yêu cầu trả phòng
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use ($contract, $validated) {

        /*
        |--------------------------------------------------------------------------
        | Tạo yêu cầu trả phòng
        |--------------------------------------------------------------------------
        */

        ContractTerminationRequest::create([
            'contract_id' => $contract->id,

            'tenant_id' => $contract->tenant_id,

            'requested_end_date' => $validated['requested_end_date'],

            'reason' => $validated['reason'],

            'status' => 'pending',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Ghi lịch sử hợp đồng
        |--------------------------------------------------------------------------
        */

        ContractHistoryService::log(
            $contract,

            ContractHistoryService::TERMINATION_REQUESTED,

            'Khách thuê đã gửi yêu cầu trả phòng.',

            $validated['reason'],

            [
                'contract_status' => $contract->status,
                'request_status' => null,
            ],

            [
                'contract_status' => $contract->status,
                'request_status' => 'pending',
                'requested_end_date' => $validated['requested_end_date'],
            ]
        );
    });

        return redirect()
            ->route('client.termination-requests.index')
            ->with(
                'success',
                'Gửi yêu cầu trả phòng thành công.'
            );
    }
}