<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
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
        $filters = $request->validate([
            'status' => 'nullable|in:new,in_progress,resolved,rejected',
            'category' => 'nullable|in:repair,invoice,utility,contract,other',
        ]);

        $requests = SupportRequest::with(['user', 'tenant', 'contract.room', 'handler'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.support.index', compact('requests'));
    }

    public function update(Request $request, SupportRequest $supportRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,resolved,rejected',
            'admin_response' => 'nullable|string|max:5000',
        ]);

        if (in_array($data['status'], [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true)
            && blank($data['admin_response'] ?? null)) {
            return back()->withErrors(['admin_response' => 'Cần nhập phản hồi khi hoàn thành hoặc từ chối yêu cầu.']);
        }

        DB::transaction(function () use ($data, $request, $supportRequest) {
            $lockedRequest = SupportRequest::query()->lockForUpdate()->findOrFail($supportRequest->id);

            if (in_array($lockedRequest->status, [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Yêu cầu đã kết thúc và không thể cập nhật lại.',
                ]);
            }

            $response = filled($data['admin_response'] ?? null) ? $data['admin_response'] : null;
            $lockedRequest->update([
                'status' => $data['status'],
                'admin_response' => $response,
                'handled_by' => $request->user()->id,
                'responded_at' => $response ? now() : null,
            ]);
            if (in_array($data['status'], [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REJECTED], true)) {
                app(AdminNotificationService::class)->resolve('support_request', $lockedRequest);
            }
        });

        $supportRequest->refresh();
        $statusLabel = match ($supportRequest->status) {
            SupportRequest::STATUS_IN_PROGRESS => 'đang được xử lý',
            SupportRequest::STATUS_RESOLVED => 'đã hoàn thành',
            SupportRequest::STATUS_REJECTED => 'đã bị từ chối',
            default => 'đã được tiếp nhận',
        };
        app(ClientNotificationService::class)->support(
            $supportRequest,
            'Yêu cầu hỗ trợ '.$statusLabel,
            filled($supportRequest->admin_response)
                ? $supportRequest->admin_response
                : 'Yêu cầu “'.$supportRequest->subject.'” '.$statusLabel.'.'
        );

        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }

    public function attachment(SupportRequest $supportRequest): StreamedResponse
    {
        abort_unless($supportRequest->attachment && Storage::disk('local')->exists($supportRequest->attachment), 404);

        return Storage::disk('local')->download($supportRequest->attachment);
    }
}
