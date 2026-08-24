<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\SupportRequest;
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
            ->latest()
            ->paginate(10);

        return view('client.support.index', compact('requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'submission_token' => 'required|uuid|unique:support_requests,submission_token',
            'category' => 'required|in:repair,invoice,utility,contract,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'attachment' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $attachment = null;
        if ($request->hasFile('attachment')) {
            $attachment = $request->file('attachment')->store('support-requests', 'local');
        }

        try {
            DB::transaction(function () use ($attachment, $data, $request) {
                $tenant = $request->user()->tenant()->lockForUpdate()->first();
                $activeContract = $tenant?->contracts()
                    ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
                    ->latest('start_date')
                    ->lockForUpdate()
                    ->first();

                if (! $tenant || ! $activeContract) {
                    throw ValidationException::withMessages([
                        'contract' => 'Không tìm thấy hợp đồng đang hoạt động để gửi yêu cầu hỗ trợ.',
                    ]);
                }

                $supportRequest = SupportRequest::create(array_merge($data, [
                    'attachment' => $attachment,
                    'user_id' => $request->user()->id,
                    'tenant_id' => $tenant->id,
                    'contract_id' => $activeContract->id,
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

        return Storage::disk('local')->download($supportRequest->attachment);
    }
}
