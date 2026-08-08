<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $supportRequest->update([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'] ?? null,
            'handled_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }
}
