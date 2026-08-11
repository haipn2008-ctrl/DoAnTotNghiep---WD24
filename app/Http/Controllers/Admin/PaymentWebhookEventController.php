<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PaymentWebhookEvent;
use App\Services\PaymentWebhookProcessor;
use Illuminate\Http\Request;

class PaymentWebhookEventController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:matched,unmatched'],
        ]);
        $events = PaymentWebhookEvent::with(['invoice', 'payment'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('transaction_at')->latest('id')->paginate(20)->withQueryString();

        return view('admin.invoices.webhook-events', compact('events'));
    }

    public function reconcile(Request $request, PaymentWebhookEvent $event, PaymentWebhookProcessor $processor)
    {
        $data = $request->validate([
            'invoice_code' => ['required', 'string', 'exists:invoices,invoice_code'],
        ]);
        $invoice = Invoice::where('invoice_code', $data['invoice_code'])->firstOrFail();
        $processor->reconcile($event, $invoice);

        return back()->with('success', 'Đã đối soát giao dịch với hóa đơn '.$invoice->invoice_code.'.');
    }
}
