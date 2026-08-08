<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'category' => 'required|in:repair,invoice,utility,contract,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'attachment' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $tenant = $request->user()->tenant;
        $activeContract = $tenant?->contracts()->where('status', 'active')->latest('start_date')->first();

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('support-requests', 'public');
        }

        SupportRequest::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'tenant_id' => $tenant?->id,
            'contract_id' => $activeContract?->id,
            'status' => SupportRequest::STATUS_NEW,
        ]));

        return back()->with('success', 'Đã gửi yêu cầu hỗ trợ đến ban quản lý.');
    }
}
