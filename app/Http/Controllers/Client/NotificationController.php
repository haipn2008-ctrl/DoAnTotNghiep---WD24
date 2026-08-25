<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('client.notifications.index', compact('notifications'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $data = $notification->data;
        $target = match ($data['type'] ?? null) {
            'extension_terms_offered' => route('client.extension-requests.index'),
            default => match ($data['action'] ?? null) {
            'invoice' => ! empty($data['invoice_id'])
                ? route('client.invoices.show', $data['invoice_id'])
                : null,
            'contract' => ! empty($data['contract_id'])
                ? route('client.contracts.show', $data['contract_id'])
                : null,
            'support' => route('client.support.index'),
            'vehicles' => route('client.vehicles.index'),
            default => ! empty($data['invoice_id'])
                ? route('client.invoices.show', $data['invoice_id'])
                : null,
            },
        };

        return $target
            ? redirect()->to($target)
            : redirect()->route('client.notifications.index');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
