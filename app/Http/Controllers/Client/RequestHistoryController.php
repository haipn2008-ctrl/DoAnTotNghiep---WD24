<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use Illuminate\Support\Facades\Auth;

class RequestHistoryController extends Controller
{
    /**
     * Lịch sử tất cả yêu cầu của khách thuê
     */
    public function index()
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | YÊU CẦU GIA HẠN
        |--------------------------------------------------------------------------
        */
        $extensionRequests = ContractExtensionRequest::with([
                'contract.room'
            ])
            ->whereHas('contract.tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->map(function ($request) {

                return [
                    'id' => $request->id,

                    'type' => 'extension',

                    'type_label' => 'Gia hạn hợp đồng',

                    'contract_code' =>
                        $request->contract?->contract_code ?? '-',

                    'room_code' =>
                        $request->contract?->room?->room_code ?? '-',

                    'current_end_date' =>
                        $request->current_end_date,

                    'requested_date' =>
                        $request->requested_end_date,

                    'reason' =>
                        $request->reason,

                    'status' =>
                        $request->status,

                    'admin_note' =>
                        $request->admin_note ?? null,

                    'created_at' =>
                        $request->created_at,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | YÊU CẦU TRẢ PHÒNG
        |--------------------------------------------------------------------------
        */
        $terminationRequests = ContractTerminationRequest::with([
                'contract.room'
            ])
            ->whereHas('contract.tenant', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->map(function ($request) {

                return [
                    'id' => $request->id,

                    'type' => 'termination',

                    'type_label' => 'Trả phòng',

                    'contract_code' =>
                        $request->contract?->contract_code ?? '-',

                    'room_code' =>
                        $request->contract?->room?->room_code ?? '-',

                    'current_end_date' =>
                        $request->contract?->end_date,

                    'requested_date' =>
                        $request->requested_end_date,

                    'reason' =>
                        $request->reason,

                    'status' =>
                        $request->status,

                    'admin_note' =>
                        $request->admin_note ?? null,

                    'created_at' =>
                        $request->created_at,
                ];
            });


        /*
        |--------------------------------------------------------------------------
        | GỘP LỊCH SỬ
        |--------------------------------------------------------------------------
        | Gia hạn + trả phòng
        | Yêu cầu mới nhất nằm trên cùng
        */
        $requests = $extensionRequests
            ->concat($terminationRequests)
            ->sortByDesc('created_at')
            ->values();


        return view(
            'client.requests.history',
            compact('requests')
        );
    }
}