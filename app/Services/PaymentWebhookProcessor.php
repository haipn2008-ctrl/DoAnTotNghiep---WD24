<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentWebhookProcessor
{
    public function process(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $existing = PaymentWebhookEvent::where('provider_transaction_id', $data['transaction_id'])
                ->lockForUpdate()->first();

            if ($existing) {
                return ['duplicate' => true, 'event' => $existing];
            }

            $invoiceCode = $this->invoiceCodeFrom((string) $data['content']);
            $invoice = $invoiceCode
                ? Invoice::whereRaw('UPPER(invoice_code) = ?', [mb_strtoupper($invoiceCode)])->lockForUpdate()->first()
                : null;

            $tenantCode = $this->tenantCodeFrom((string) $data['content']);
            if (! $invoice && ! $invoiceCode && $tenantCode) {
                $tenant = Tenant::where('payment_code', $tenantCode)->lockForUpdate()->first();

                if ($tenant) {
                    return $this->processTenantPayment($data, $tenant);
                }
            }

            if (! $invoice) {
                $event = $this->event($data, [
                    'status' => PaymentWebhookEvent::STATUS_UNMATCHED,
                    'message' => $invoiceCode
                        ? "Không tìm thấy hóa đơn {$invoiceCode}."
                        : ($tenantCode
                            ? "Không tìm thấy khách thuê có mã {$tenantCode}."
                            : 'Nội dung chuyển khoản không có mã hóa đơn hoặc mã khách hợp lệ.'),
                ]);

                return ['duplicate' => false, 'event' => $event];
            }

            $amount = (float) $data['amount'];
            if ($amount > (float) $invoice->remaining_amount) {
                $event = $this->event($data, [
                    'invoice_id' => $invoice->id,
                    'status' => PaymentWebhookEvent::STATUS_UNMATCHED,
                    'message' => 'Số tiền chuyển vượt quá số còn phải trả của '.$invoice->invoice_code.'.',
                ]);

                return ['duplicate' => false, 'event' => $event];
            }
            $pendingPayment = $invoice->payments()->pending()
                ->where('amount_paid', $amount)
                ->oldest('id')->lockForUpdate()->first();

            if ($pendingPayment) {
                $pendingPayment->update([
                    'transaction_code' => $data['transaction_id'],
                    'payment_date' => Carbon::parse($data['transaction_date'])->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'status' => Payment::STATUS_SUCCESS,
                    'reviewed_at' => now(),
                    'review_note' => 'Tự động đối soát qua webhook ngân hàng.',
                ]);
                $payment = $pendingPayment;
            } else {
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount_paid' => $amount,
                    'payment_date' => Carbon::parse($data['transaction_date'])->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'transaction_code' => $data['transaction_id'],
                    'status' => Payment::STATUS_SUCCESS,
                    'reviewed_at' => now(),
                    'review_note' => 'Tự động ghi nhận qua webhook ngân hàng.',
                    'note' => $data['content'],
                ]);
            }

            $invoice->refreshStatus();
            $tenant = $invoice->contract()->with('tenant.user')->first()?->tenant;
            if ($tenant) {
                app(TenantAccountLifecycle::class)->sync($tenant);
            }

            $event = $this->event($data, [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'status' => PaymentWebhookEvent::STATUS_MATCHED,
                'message' => 'Đã tự động đối soát với '.$invoice->invoice_code.'.',
            ]);

            return ['duplicate' => false, 'event' => $event];
        });
    }

    public function reconcile(PaymentWebhookEvent $event, Invoice $invoice): PaymentWebhookEvent
    {
        return DB::transaction(function () use ($event, $invoice) {
            $event = PaymentWebhookEvent::lockForUpdate()->findOrFail($event->id);
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);

            if ($event->status !== PaymentWebhookEvent::STATUS_UNMATCHED || $event->payment_id) {
                throw ValidationException::withMessages([
                    'event' => 'Giao dịch webhook này đã được đối soát trước đó.',
                ]);
            }

            $pendingPayment = $invoice->payments()->pending()
                ->where('amount_paid', $event->amount)->oldest('id')->lockForUpdate()->first();

            if ($pendingPayment) {
                $pendingPayment->update([
                    'transaction_code' => $event->provider_transaction_id,
                    'payment_date' => $event->transaction_at->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'status' => Payment::STATUS_SUCCESS,
                    'reviewed_at' => now(),
                    'review_note' => 'Quản trị viên đối soát từ webhook ngân hàng.',
                ]);
                $payment = $pendingPayment;
            } else {
                $payment = Payment::create([
                    'invoice_id' => $invoice->id, 'amount_paid' => $event->amount,
                    'payment_date' => $event->transaction_at->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'transaction_code' => $event->provider_transaction_id,
                    'status' => Payment::STATUS_SUCCESS, 'reviewed_at' => now(),
                    'review_note' => 'Quản trị viên đối soát từ webhook ngân hàng.',
                    'note' => $event->content,
                ]);
            }

            $invoice->refreshStatus();
            $tenant = $invoice->contract()->with('tenant.user')->first()?->tenant;
            if ($tenant) {
                app(TenantAccountLifecycle::class)->sync($tenant);
            }

            $event->update([
                'invoice_id' => $invoice->id, 'payment_id' => $payment->id,
                'status' => PaymentWebhookEvent::STATUS_MATCHED,
                'message' => 'Đã đối soát thủ công với '.$invoice->invoice_code.'.',
            ]);

            return $event->fresh();
        });
    }

    private function invoiceCodeFrom(string $content): ?string
    {
        preg_match('/\bINV-\d{6}-\d{6}\b/i', $content, $matches);

        return isset($matches[0]) ? mb_strtoupper($matches[0]) : null;
    }

    private function tenantCodeFrom(string $content): ?string
    {
        preg_match('/\bKH\d{8}\b/i', $content, $matches);

        return isset($matches[0]) ? mb_strtoupper($matches[0]) : null;
    }

    private function processTenantPayment(array $data, Tenant $tenant): array
    {
        $invoices = Invoice::whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->orderBy('due_date')->orderBy('id')->lockForUpdate()->get();
        $amount = (float) $data['amount'];
        $totalOutstanding = (float) $invoices->sum(fn ($invoice) => $invoice->remaining_amount);

        if ($invoices->isEmpty() || $amount > $totalOutstanding) {
            $message = $invoices->isEmpty()
                ? "Khách {$tenant->payment_code} không có hóa đơn còn nợ."
                : 'Số tiền chuyển vượt quá tổng công nợ của khách '.$tenant->payment_code.'.';
            $event = $this->event($data, [
                'status' => PaymentWebhookEvent::STATUS_UNMATCHED,
                'message' => $message,
            ]);

            return ['duplicate' => false, 'event' => $event];
        }

        $remaining = $amount;
        $allocations = [];
        foreach ($invoices as $invoice) {
            if ($remaining <= 0) {
                break;
            }
            $allocated = min($remaining, (float) $invoice->remaining_amount);
            $allocations[] = [$invoice, $allocated];
            $remaining -= $allocated;
        }

        $payments = [];
        $count = count($allocations);
        foreach ($allocations as $index => [$invoice, $allocated]) {
            $transactionCode = $count === 1
                ? $data['transaction_id']
                : $data['transaction_id'].'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $pending = $invoice->payments()->pending()->where('amount_paid', $allocated)
                ->oldest('id')->lockForUpdate()->first();

            if ($pending) {
                $pending->update([
                    'transaction_code' => $transactionCode,
                    'payment_date' => Carbon::parse($data['transaction_date'])->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'status' => Payment::STATUS_SUCCESS, 'reviewed_at' => now(),
                    'review_note' => 'Tự động đối soát bằng mã khách '.$tenant->payment_code.'.',
                ]);
                $payment = $pending;
            } else {
                $payment = Payment::create([
                    'invoice_id' => $invoice->id, 'amount_paid' => $allocated,
                    'payment_date' => Carbon::parse($data['transaction_date'])->toDateString(),
                    'payment_method' => Payment::METHOD_BANK_TRANSFER,
                    'transaction_code' => $transactionCode, 'status' => Payment::STATUS_SUCCESS,
                    'reviewed_at' => now(),
                    'review_note' => 'Tự động đối soát bằng mã khách '.$tenant->payment_code.'.',
                    'note' => $data['content'],
                ]);
            }
            $invoice->refreshStatus();
            $payments[] = $payment;
        }

        app(TenantAccountLifecycle::class)->sync($tenant->loadMissing('user'));
        $codes = collect($allocations)->map(fn ($item) => $item[0]->invoice_code)->implode(', ');
        $event = $this->event($data, [
            'invoice_id' => $allocations[0][0]->id,
            'payment_id' => $payments[0]->id,
            'status' => PaymentWebhookEvent::STATUS_MATCHED,
            'message' => "Đã tự động phân bổ cho {$codes} bằng mã {$tenant->payment_code}.",
        ]);

        return ['duplicate' => false, 'event' => $event];
    }

    private function event(array $data, array $attributes): PaymentWebhookEvent
    {
        return PaymentWebhookEvent::create(array_merge([
            'provider_transaction_id' => $data['transaction_id'],
            'amount' => $data['amount'],
            'content' => $data['content'],
            'transaction_at' => $data['transaction_date'],
            'payload' => $data,
        ], $attributes));
    }
}
