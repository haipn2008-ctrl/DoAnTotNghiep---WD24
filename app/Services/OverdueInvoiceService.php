<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Payment;
use App\Notifications\InvoiceDueTodayNotification;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OverdueInvoiceService
{
    public function notifyDueToday(): int
    {
        return $this->notifyInvoices(
            fn (Builder $query) => $query
                ->whereNull('due_notified_at')
                ->whereRaw('DATE(COALESCE(payment_extension_until, due_date)) = ?', [today()->toDateString()]),
            fn (Invoice $invoice): bool => ! $invoice->due_notified_at && $invoice->effective_due_date->isToday(),
            function (Invoice $invoice): void {
                $invoice->update(['due_notified_at' => now()]);
                $invoice->contract->tenant->user->notify(new InvoiceDueTodayNotification($invoice));
            }
        );
    }

    public function notifyNewlyOverdue(): int
    {
        return $this->notifyInvoices(
            fn (Builder $query) => $query
                ->whereNull('overdue_notified_at')
                ->whereRaw('DATE(COALESCE(payment_extension_until, due_date)) < ?', [today()->toDateString()]),
            fn (Invoice $invoice): bool => ! $invoice->overdue_notified_at && today()->gt($invoice->effective_due_date),
            function (Invoice $invoice): void {
                $invoice->reminders()->firstOrCreate(
                    ['reminder_date' => today()->toDateString()],
                    [
                        'channel' => InvoiceReminder::CHANNEL_SYSTEM,
                        'note' => 'Hệ thống tự động thông báo hóa đơn quá hạn và yêu cầu khách gửi lý do chậm thanh toán.',
                        'reminded_by' => null,
                        'reminded_by_name' => 'Hệ thống',
                        'reminded_at' => now(),
                    ]
                );
                $invoice->update(['overdue_notified_at' => now()]);
                $invoice->contract->tenant->user->notify(new InvoiceOverdueNotification($invoice));
            }
        );
    }

    private function notifyInvoices(callable $scope, callable $eligible, callable $notify): int
    {
        $query = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL]);
        $ids = $scope($query)->orderBy('id')->pluck('id');
        $notified = 0;

        foreach ($ids as $id) {
            $sent = DB::transaction(function () use ($id, $eligible, $notify): bool {
                $invoice = Invoice::query()
                    ->with(['contract.tenant.user', 'room', 'payments'])
                    ->lockForUpdate()
                    ->findOrFail($id);
                $paid = (float) $invoice->payments
                    ->where('status', Payment::STATUS_SUCCESS)
                    ->sum('amount_paid');
                $pending = (float) $invoice->payments
                    ->where('status', Payment::STATUS_PENDING)
                    ->sum('amount_paid');
                $remaining = max(0, $invoice->payable_amount - $paid);
                $recipient = $invoice->contract?->tenant?->user;

                if (! $eligible($invoice) || ! $invoice->canPay() || ! $recipient || $remaining <= 0 || $pending >= $remaining) {
                    return false;
                }

                $notify($invoice);

                return true;
            }, 3);

            if ($sent) {
                $notified++;
            }
        }

        return $notified;
    }
}
