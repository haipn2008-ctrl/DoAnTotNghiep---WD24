<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Payment;
use App\Notifications\InvoicePaymentReminderNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DebtController extends Controller
{
    public const BUCKETS = [
        'upcoming' => 'Chưa đến hạn',
        'due_today' => 'Đến hạn hôm nay',
        'overdue_1_3' => 'Quá hạn 1–3 ngày',
        'overdue_4_7' => 'Quá hạn 4–7 ngày',
        'overdue_8_14' => 'Quá hạn 8–14 ngày',
        'overdue_15_plus' => 'Quá hạn từ 15 ngày',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'bucket' => ['nullable', Rule::in(array_keys(self::BUCKETS))],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);
        $bucket = $filters['bucket'] ?? null;
        $keyword = trim($filters['keyword'] ?? '');

        $query = $this->debtQuery($keyword);
        $this->applyBucket($query, $bucket);

        $summary = DB::query()
            ->fromSub((clone $query)->reorder()->toBase(), 'debts')
            ->selectRaw('COUNT(*) AS invoice_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN total_amount + adjustment_amount - paid_amount > 0 THEN total_amount + adjustment_amount - paid_amount ELSE 0 END), 0) AS remaining_amount')
            ->selectRaw('COALESCE(SUM(pending_amount), 0) AS pending_amount')
            ->first();

        $invoices = $query
            ->with(['contract.tenant', 'room', 'reminders.remindedBy'])
            ->withCount('reminders')
            ->orderByRaw('CASE WHEN due_date < ? THEN 0 WHEN due_date = ? THEN 1 ELSE 2 END', [today()->toDateString(), today()->toDateString()])
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.debts.index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'buckets' => self::BUCKETS,
            'bucket' => $bucket,
            'keyword' => $keyword,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'contract.tenant',
            'room',
            'payments',
            'reminders.remindedBy',
        ]);
        $paidAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount_paid');
        $pendingAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_PENDING)
            ->sum('amount_paid');
        $remainingAmount = max(0, $invoice->payable_amount - $paidAmount);
        $remindedToday = $invoice->reminders
            ->contains(fn (InvoiceReminder $reminder) => $reminder->reminder_date->isToday());
        $canRemind = $invoice->canPay() && $remainingAmount > 0 && ! $remindedToday;

        return view('admin.debts.show', compact(
            'invoice',
            'paidAmount',
            'pendingAmount',
            'remainingAmount',
            'remindedToday',
            'canRemind'
        ));
    }

    public function storeReminder(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'note.max' => 'Nội dung nhắc không được vượt quá 1000 ký tự.',
        ]);

        DB::transaction(function () use ($data, $invoice, $request): void {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $paidAmount = (float) $lockedInvoice->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->sum('amount_paid');

            if (! $lockedInvoice->canPay() || $lockedInvoice->payable_amount - $paidAmount <= 0) {
                throw ValidationException::withMessages([
                    'reminder' => 'Hóa đơn không còn công nợ cần nhắc.',
                ]);
            }

            if ($lockedInvoice->reminders()->whereDate('reminder_date', today())->exists()) {
                throw ValidationException::withMessages([
                    'reminder' => 'Hóa đơn này đã được ghi nhận nhắc trong hôm nay.',
                ]);
            }

            $lockedInvoice->loadMissing(['contract.tenant.user', 'room']);
            $recipient = $lockedInvoice->contract?->tenant?->user;
            if (! $recipient) {
                throw ValidationException::withMessages([
                    'reminder' => 'Khách thuê chưa có tài khoản hệ thống để nhận thông báo.',
                ]);
            }

            $remainingAmount = max(0, $lockedInvoice->payable_amount - $paidAmount);

            $lockedInvoice->reminders()->create([
                'channel' => InvoiceReminder::CHANNEL_SYSTEM,
                'note' => $data['note'] ?? null,
                'reminded_by' => $request->user()->id,
                'reminded_by_name' => $request->user()->name,
                'reminder_date' => today()->toDateString(),
                'reminded_at' => now(),
            ]);

            $recipient->notify(new InvoicePaymentReminderNotification(
                $lockedInvoice,
                $remainingAmount,
                $data['note'] ?? null,
            ));
        });

        return redirect()
            ->route('admin.debts.show', $invoice)
            ->with('success', 'Đã gửi thông báo nhắc thanh toán đến tài khoản khách thuê.');
    }

    private function debtQuery(string $keyword): Builder
    {
        $paidSubquery = Payment::query()
            ->selectRaw('COALESCE(SUM(amount_paid), 0)')
            ->whereColumn('invoice_id', 'invoices.id')
            ->where('status', Payment::STATUS_SUCCESS);
        $pendingSubquery = Payment::query()
            ->selectRaw('COALESCE(SUM(amount_paid), 0)')
            ->whereColumn('invoice_id', 'invoices.id')
            ->where('status', Payment::STATUS_PENDING);

        return Invoice::query()
            ->select('invoices.*')
            ->selectSub($paidSubquery, 'paid_amount')
            ->selectSub($pendingSubquery, 'pending_amount')
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->whereRaw('total_amount + adjustment_amount > 0')
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('invoice_code', 'like', "%{$keyword}%")
                        ->orWhereHas('room', fn (Builder $room) => $room->where('room_code', 'like', "%{$keyword}%"))
                        ->orWhereHas('contract', function (Builder $contract) use ($keyword): void {
                            $contract->where('contract_code', 'like', "%{$keyword}%")
                                ->orWhereHas('tenant', function (Builder $tenant) use ($keyword): void {
                                    $tenant->where('full_name', 'like', "%{$keyword}%")
                                        ->orWhere('phone', 'like', "%{$keyword}%");
                                });
                        });
                });
            });
    }

    private function applyBucket(Builder $query, ?string $bucket): void
    {
        $today = today();

        match ($bucket) {
            'upcoming' => $query->whereDate('due_date', '>', $today),
            'due_today' => $query->whereDate('due_date', $today),
            'overdue_1_3' => $query->whereBetween('due_date', [$today->copy()->subDays(3), $today->copy()->subDay()]),
            'overdue_4_7' => $query->whereBetween('due_date', [$today->copy()->subDays(7), $today->copy()->subDays(4)]),
            'overdue_8_14' => $query->whereBetween('due_date', [$today->copy()->subDays(14), $today->copy()->subDays(8)]),
            'overdue_15_plus' => $query->whereDate('due_date', '<=', $today->copy()->subDays(15)),
            default => null,
        };
    }
}
