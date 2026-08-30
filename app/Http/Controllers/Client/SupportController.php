<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\SupportRequest;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $requests = SupportRequest::where('user_id', $request->user()->id)
            ->with('contract.room')
            ->latest()
            ->paginate(10);

        $eligibleContracts = Contract::query()
            ->managedBy($request->user())
            ->whereIn('status', array_merge(Contract::OPEN_OCCUPANCY_STATUSES, [Contract::STATUS_SETTLING]))
            ->with('room')
            ->latest('start_date')
            ->get();

        $canCreateSupport = in_array($request->user()->status, [
            User::STATUS_ACTIVE,
            User::STATUS_SETTLING,
        ], true) && $eligibleContracts->isNotEmpty();

        return view('client.support.index', compact('requests', 'eligibleContracts', 'canCreateSupport'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'submission_token' => 'required|uuid|unique:support_requests,submission_token',
            'contract_id' => 'nullable|integer',
            'category' => 'required|in:repair,invoice,utility,contract,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'attachment' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (! in_array($request->user()->status, [User::STATUS_ACTIVE, User::STATUS_SETTLING], true)) {
            throw ValidationException::withMessages([
                'contract' => 'Tài khoản khách cũ chỉ được xem lịch sử hỗ trợ.',
            ]);
        }

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('support-requests', 'local');
        }

        try {
            DB::transaction(function () use ($attachment, $data, $request) {
                $tenant = $request->user()->tenant()->lockForUpdate()->first();
                $supportContract = $tenant?->contracts()
                    ->whereIn('status', array_merge(Contract::OPEN_OCCUPANCY_STATUSES, [Contract::STATUS_SETTLING]))
                    ->when(
                        $data['contract_id'] ?? null,
                        fn ($query, $contractId) => $query->whereKey($contractId)
                    )
                    ->orderByRaw('CASE WHEN status IN (?, ?) THEN 0 ELSE 1 END', Contract::OPEN_OCCUPANCY_STATUSES)
                    ->latest('start_date')
                    ->lockForUpdate()
                    ->first();

                if (! $tenant || ! $supportContract) {
                    throw ValidationException::withMessages([
                        'contract' => 'Không tìm thấy hợp đồng đang thuê hoặc đang quyết toán thuộc tài khoản này.',
                    ]);
                }

                $supportRequest = SupportRequest::create(array_merge($data, [
                    'attachment' => $attachment,
                    'user_id' => $request->user()->id,
                    'tenant_id' => $tenant->id,
                    'contract_id' => $supportContract->id,
                    'status' => SupportRequest::STATUS_NEW,
                ]));

                app(AdminNotificationService::class)->supportRequested($supportRequest);
            });
        } catch (\Throwable $exception) {
            if ($attachment) {
                Storage::disk('local')->delete($attachment);
            }

            throw $exception;
        }

        return back()->with('success', 'Đã gửi yêu cầu hỗ trợ đến ban quản lý.');
    }

    public function attachment(Request $request, int $supportRequest): StreamedResponse
    {
        $supportRequest = SupportRequest::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($supportRequest);

        abort_unless($supportRequest->attachment && Storage::disk('local')->exists($supportRequest->attachment), 404);

        return Storage::disk('local')->response($supportRequest->attachment, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
