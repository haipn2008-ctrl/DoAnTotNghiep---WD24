<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\AdminNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => 'nullable|in:unpaid,partial,paid,cancelled',
            'year' => 'nullable|integer|between:2000,2100',
        ]);

        $query = $this->invoiceQuery($request)
            ->with(['room', 'contract'])
            ->withSum(
                ['payments as paid_amount' => fn ($query) => $query->where('status', Payment::STATUS_SUCCESS),
                ],
                'amount_paid'
            );

        $query->when(
            $filters['status'] ?? null,
            fn ($query, $status) => $query->where('status', $status)
        );

        $query->when(
            $filters['year'] ?? null,
            fn ($query, $year) => $query->where('year', $year)
        );

        $invoices = $query
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $years = $this->invoiceQuery($request)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('client.invoices.index', compact('invoices', 'years'));
    }

    public function show(Request $request, int $invoice): View
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);

        return view('client.invoices.show', $this->invoiceViewData($invoice));
    }

    public function print(Request $request, int $invoice): View
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);

        return view('client.invoices.print', $this->invoiceViewData($invoice));
    }

    public function storePayment(Request $request, int $invoice): RedirectResponse
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);
        if (! $invoice->canPay()) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Hóa đơn không còn nhận thanh toán.',
            ]);
        }
        $paidAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount_paid');

        $pendingAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_PENDING)
            ->sum('amount_paid');

        $availableAmount = max(
            0,
            $invoice->payable_amount - $paidAmount - $pendingAmount
        );

        if ($availableAmount <= 0) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Hóa đơn đã được thanh toán đủ hoặc đang có xác nhận chờ duyệt.',
            ]);
        }

        $data = $request->validate([
            'amount_paid' => [
                'required',
                'integer',
                'min:1',
                'max:'.(int) floor($availableAmount),
            ],
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'note' => 'nullable|string|max:1000',
        ]);

        $proofPath = $request->file('proof_image')
            ->store('payment-proofs', 'local');

        try {
            DB::transaction(function () use (
                $data,
                $invoice,
                $proofPath,
                $request
            ) {
                $lockedInvoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($invoice->id);

                // Kiểm tra lại quyền sở hữu sau khi khóa bản ghi.
                $tenantId = $request->user()->tenant?->id;

                if (
                    ! $tenantId ||
                    ! $lockedInvoice->contract ||
                    $lockedInvoice->contract->tenant_id !== $tenantId
                ) {
                    abort(403, 'Bạn không có quyền thanh toán hóa đơn này.');
                }

                $reservedAmount = (float) $lockedInvoice
                    ->payments()
                    ->whereIn('status', [
                        Payment::STATUS_SUCCESS,
                        Payment::STATUS_PENDING,
                    ])
                    ->sum('amount_paid');

                $availableAmount = max(
                    0,
                    $lockedInvoice->payable_amount - $reservedAmount
                );

                if (! $lockedInvoice->canPay()) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Hóa đơn không còn nhận thanh toán.',
                    ]);
                }

                if ((float) $data['amount_paid'] > $availableAmount) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Số tiền xác nhận vượt quá số tiền còn có thể thanh toán.',
                    ]);
                }

                $payment = Payment::create([
                    'invoice_id' => $lockedInvoice->id,
                    'amount_paid' => $data['amount_paid'],
                    'payment_date' => now()->toDateString(),
                    'payment_method' => Payment::METHOD_QR,
                    'transaction_code' => null,
                    'status' => Payment::STATUS_PENDING,
                    'submitted_by' => $request->user()->id,
                    'proof_image' => $proofPath,
                    'note' => $data['note'] ?? null,
                ]);

                app(AdminNotificationService::class)->paymentSubmitted($payment);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        return redirect()
            ->route('client.invoices.show', $invoice)
            ->with(
                'success',
                'Đã gửi xác nhận thanh toán. Ban quản lý sẽ kiểm tra và phản hồi.'
            );
    }

    public function paymentProof(Request $request, Payment $payment): BinaryFileResponse
    {
        $tenantId = $request->user()->tenant?->id;

        abort_unless(
            $tenantId
            && $payment->invoice()
                ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenantId))
                ->exists(),
            404
        );

        return $this->privateProofResponse($payment);
    }

    private function findOwnedInvoice(Request $request, int $invoice): Invoice
    {
        return $this->invoiceQuery($request)
            ->with([
                'room',
                'contract.tenant',
                'details',
                'adjustments.creator',
                'payments' => fn ($query) => $query->latest('payment_date')->latest('id'),
            ])
            ->findOrFail($invoice);
    }

    private function invoiceViewData(Invoice $invoice): array
    {
        $paidAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount_paid');

        $remainingAmount = max(
            0,
            $invoice->payable_amount - $paidAmount
        );

        $pendingAmount = (float) $invoice->payments
            ->where('status', Payment::STATUS_PENDING)
            ->sum('amount_paid');

        $availableAmount = max(
            0,
            $remainingAmount - $pendingAmount
        );

        $bankSetting = Setting::currentOrCreate();
        $paymentContent = 'TT '.$invoice->invoice_code;

        return compact(
            'invoice',
            'paidAmount',
            'remainingAmount',
            'pendingAmount',
            'availableAmount',
            'bankSetting',
            'paymentContent'
        );
    }

    private function invoiceQuery(Request $request): Builder
    {
        $tenantId = $request->user()->tenant?->id;

        return Invoice::query()
            ->when(
                $tenantId,
                fn ($query) => $query->whereHas(
                    'contract',
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
                fn ($query) => $query->whereRaw('1 = 0')
            );
    }

    private function privateProofResponse(Payment $payment): BinaryFileResponse
    {
        abort_unless(
            filled($payment->proof_image)
            && str_starts_with($payment->proof_image, 'payment-proofs/')
            && Storage::disk('local')->exists($payment->proof_image),
            404
        );

        $response = response()->file(Storage::disk('local')->path($payment->proof_image), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
