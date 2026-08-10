<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceGenerator;
use App\Services\TenantAccountLifecycle;
use App\Support\Csv;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Danh sách hóa đơn
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', 'in:unpaid,partial,paid'],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $year = isset($filters['year']) ? (int) $filters['year'] : null;
        $status = $filters['status'] ?? null;
        $keyword = trim($filters['keyword'] ?? '');

        $query = Invoice::with([
            'contract.tenant',
            'room',
            'payments',
        ])
            ->withSum([
                'payments as paid_amount' => function ($q) {
                    $q->success();
                },
            ], 'amount_paid')
            ->latest('invoice_date')
            ->latest('id');

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if (in_array($status, [
            Invoice::STATUS_UNPAID,
            Invoice::STATUS_PARTIAL,
            Invoice::STATUS_PAID,
        ])) {
            $query->where('status', $status);
        }

        if ($keyword != '') {

            $query->where(function ($q) use ($keyword) {

                $q->whereHas('room', function ($room) use ($keyword) {

                    $room->where('room_code', 'like', "%{$keyword}%");
                })
                    ->orWhereHas('contract', function ($contract) use ($keyword) {

                        $contract->where('contract_code', 'like', "%{$keyword}%")

                            ->orWhereHas('tenant', function ($tenant) use ($keyword) {

                                $tenant->where('full_name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                    });
            });
        }

        $summaryQuery = clone $query;
        $invoices = $query
            ->paginate(15)
            ->withQueryString();

        $summary = [

            'count' => (clone $summaryQuery)->count(),

            'total_amount' => (clone $summaryQuery)
                ->sum('total_amount'),

            'unpaid' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_UNPAID)
                ->count(),

            'partial' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_PARTIAL)
                ->count(),

            'paid' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_PAID)
                ->count(),

        ];

        return view(
            'admin.invoices.index',
            compact(
                'invoices',
                'month',
                'year',
                'status',
                'keyword',
                'summary'
            )
        );
    }

    /**
     * Chi tiết hóa đơn
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'contract.tenant',
            'room',
            'utilityReading',
            'details',
            'payments',
        ]);

        $paidAmount = $invoice->payments()
            ->success()
            ->sum('amount_paid');

        $remainingAmount = max(
            0,
            $invoice->total_amount - $paidAmount
        );

        return view(
            'admin.invoices.show',
            compact(
                'invoice',
                'paidAmount',
                'remainingAmount'
            )
        );
    }

    /**
     * Alias for the invoice generation form route.
     */
    public function generateForm(Request $request)
    {
        return $this->generate($request);
    }

    /**
     * Form sinh hóa đơn
     */
    public function generate(Request $request)
    {
        $period = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);
        $month = (int) ($period['month'] ?? now()->month);
        $year = (int) ($period['year'] ?? now()->year);

        // danh sách năm để hiển thị combobox
        $years = range(
            now()->year - 2,
            now()->year + 2
        );

        $periodStart = Carbon::create(
            $year,
            $month,
            1
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        $contracts = Contract::with([
            'room',
            'tenant',
        ])
            ->where('status', 'active')
            ->whereDate(
                'start_date',
                '<=',
                $periodEnd
            )
            // Kỳ hiệu lực kết thúc ưu tiên: actual_end_date > extend_end_date > end_date
            ->whereRaw(
                'COALESCE(actual_end_date, extend_end_date, end_date) >= ?',
                [$periodStart->toDateString()]
            )
            ->orderBy('id')
            ->get();

        $issuedRoomIds = Invoice::where('month', $month)
            ->where('year', $year)
            ->pluck('room_id')
            ->toArray();

        return view(
            'admin.invoices.generate',
            compact(
                'contracts',
                'month',
                'year',
                'years',
                'issuedRoomIds'
            )
        );
    }

    public function generateStore(Request $request, InvoiceGenerator $generator)
    {
        $data = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $invoice = $generator->issue(
                Contract::findOrFail($data['contract_id']),
                (int) $data['month'],
                (int) $data['year']
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        } catch (QueryException $exception) {
            return back()
                ->withErrors(['invoice' => 'Hóa đơn đã tồn tại hoặc dữ liệu không hợp lệ.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Sinh hóa đơn thành công.');
    }

    /**
     * Form xuất danh sách hóa đơn
     */
    public function exportForm(Request $request)
    {
        $filters = $this->invoiceExportFilters($request);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $year = isset($filters['year']) ? (int) $filters['year'] : null;
        $status = $filters['status'] ?? null;
        $keyword = trim($filters['keyword'] ?? '');

        $query = Invoice::with([
            'contract.tenant',
            'room',
            'payments',
        ])
            ->withSum([
                'payments as paid_amount' => function ($q) {
                    $q->success();
                },
            ], 'amount_paid')
            ->latest('invoice_date')
            ->latest('id');

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if (in_array($status, [
            Invoice::STATUS_UNPAID,
            Invoice::STATUS_PARTIAL,
            Invoice::STATUS_PAID,
        ])) {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('room', function ($room) use ($keyword) {
                    $room->where('room_code', 'like', "%{$keyword}%");
                })
                    ->orWhereHas('contract', function ($contract) use ($keyword) {
                        $contract->where('contract_code', 'like', "%{$keyword}%")
                            ->orWhereHas('tenant', function ($tenant) use ($keyword) {
                                $tenant->where('full_name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                    });
            });
        }

        $invoices = $query
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.invoices.export',
            compact('invoices')
        );
    }

    /**
     * Xem trước hóa đơn
     */
    public function preview(
        Request $request,
        Contract $contract,
        InvoiceGenerator $generator
    ): JsonResponse {

        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {

            $preview = $generator->preview(
                $contract,
                (int) $data['month'],
                (int) $data['year']
            );

            return response()->json([
                'contract_id' => $preview['contract']->id,
                'contract_code' => $preview['contract']->contract_code,
                'room_code' => $preview['room']->room_code,
                'tenant_name' => $preview['tenant']->full_name ?? 'Không có',
                'month' => $preview['month'],
                'year' => $preview['year'],
                'invoice_date' => $preview['invoice_date'],
                'due_date' => $preview['due_date'],
                'total_amount' => $preview['total_amount'],
                'lines' => collect($preview['lines'])->map(fn (array $line) => [
                    'type' => $line['type'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'],
                    'unit_price' => $line['unit_price'],
                    'amount' => $line['amount'],
                    'note' => $line['note'],
                ])->values(),
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    /**
     * Phát hành hóa đơn
     */
    public function issue(
        Request $request,
        Contract $contract,
        InvoiceGenerator $generator
    ): JsonResponse {

        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {

            $invoice = $generator->issue(
                $contract,
                (int) $data['month'],
                (int) $data['year']
            );

            return response()->json([
                'success' => true,
                'message' => 'Sinh hóa đơn thành công.',
                'invoice_id' => $invoice->id,
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (QueryException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Hóa đơn đã tồn tại hoặc dữ liệu không hợp lệ.',
            ], 422);
        }
    }

    /**
     * Form sửa hóa đơn
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenant',
            'details',
            'payments',
        ]);

        return view(
            'admin.invoices.edit',
            compact('invoice')
        );
    }

    /**
     * Cập nhật hóa đơn
     */
    public function update(
        Request $request,
        Invoice $invoice
    ) {
        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'error',
                'Trạng thái hóa đơn được tính tự động từ thanh toán. Vui lòng ghi nhận thanh toán tại chi tiết hóa đơn.'
            );
    }

    /**
     * Xóa hóa đơn
     */
    public function destroy(Invoice $invoice)
    {
        $deleted = DB::transaction(function () use ($invoice) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->payments()->exists()) {
                return false;
            }

            $lockedInvoice->details()->delete();
            $lockedInvoice->delete();

            return true;
        });

        if (! $deleted) {

            return redirect()
                ->route('admin.invoices.index')
                ->with(
                    'error',
                    'Không thể xóa hóa đơn đã phát sinh thanh toán.'
                );
        }

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                'Đã xóa hóa đơn thành công.'
            );
    }

    /**
     * Xuất danh sách hóa đơn ra CSV
     */
    public function export(Request $request)
    {
        $filters = $this->invoiceExportFilters($request);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $year = isset($filters['year']) ? (int) $filters['year'] : null;
        $status = $filters['status'] ?? null;
        $keyword = trim($filters['keyword'] ?? '');

        $query = Invoice::with([
            'contract.tenant',
            'room',
            'payments',
        ])
            ->withSum([
                'payments as paid_amount' => function ($q) {
                    $q->success();
                },
            ], 'amount_paid')
            ->latest('invoice_date')
            ->latest('id');

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if (in_array($status, [
            Invoice::STATUS_UNPAID,
            Invoice::STATUS_PARTIAL,
            Invoice::STATUS_PAID,
        ])) {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('room', function ($room) use ($keyword) {
                    $room->where('room_code', 'like', "%{$keyword}%");
                })
                    ->orWhereHas('contract', function ($contract) use ($keyword) {
                        $contract->where('contract_code', 'like', "%{$keyword}%")
                            ->orWhereHas('tenant', function ($tenant) use ($keyword) {
                                $tenant->where('full_name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                    });
            });
        }

        $invoices = $query->lazy(500);

        $filename = 'danh_sach_hoa_don_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Mã hóa đơn',
            'Kỳ',
            'Phòng',
            'Khách thuê',
            'Tổng tiền',
            'Đã thu',
            'Còn lại',
            'Trạng thái',
            'Ngày phát hành',
        ];

        return response()->stream(function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            Csv::writeRow($file, $columns);

            foreach ($invoices as $invoice) {
                $paidAmount = $invoice->paid_amount ?? $invoice->payments()->success()->sum('amount_paid');
                $remainingAmount = max(0, $invoice->total_amount - $paidAmount);

                Csv::writeRow($file, [
                    $invoice->invoice_code,
                    sprintf('%02d', $invoice->month).'/'.$invoice->year,
                    $invoice->room->room_code ?? '-',
                    $invoice->contract->tenant->full_name ?? '-',
                    number_format($invoice->total_amount, 0, ',', '.'),
                    number_format($paidAmount, 0, ',', '.'),
                    number_format($remainingAmount, 0, ',', '.'),
                    $invoice->status_label,
                    $invoice->invoice_date?->format('d/m/Y') ?? '-',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Danh sách thanh toán
     */
    public function payments(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_SUCCESS, Payment::STATUS_FAILED])],
            'method' => ['nullable', Rule::in([Payment::METHOD_CASH, Payment::METHOD_BANK_TRANSFER, Payment::METHOD_QR])],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
            'submitter',
        ])->latest('payment_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where(
                'payment_method',
                $filters['method']
            );
        }

        if (! empty($filters['keyword'])) {

            $keyword = trim($filters['keyword']);

            $query->where(function ($q) use ($keyword) {

                $q->where('transaction_code', 'like', "%{$keyword}%")

                    ->orWhereHas('invoice.room', function ($room) use ($keyword) {

                        $room->where(
                            'room_code',
                            'like',
                            "%{$keyword}%"
                        );
                    })

                    ->orWhereHas('invoice.contract', function ($contract) use ($keyword) {

                        $contract->where(
                            'contract_code',
                            'like',
                            "%{$keyword}%"
                        );
                    })

                    ->orWhereHas('invoice.contract.tenant', function ($tenant) use ($keyword) {

                        $tenant->where(
                            'full_name',
                            'like',
                            "%{$keyword}%"
                        );
                    });
            });
        }

        $payments = $query
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.invoices.payments',
            compact('payments')
        );
    }

    /**
     * Form xuất danh sách thanh toán
     */
    public function exportPaymentsForm(Request $request)
    {
        $filters = $this->paymentExportFilters($request);
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
        ])->latest('payment_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where(
                'payment_method',
                $filters['method']
            );
        }

        if (! empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);

            $query->where(function ($q) use ($keyword) {
                $q->where('transaction_code', 'like', "%{$keyword}%")
                    ->orWhereHas('invoice.room', function ($room) use ($keyword) {
                        $room->where('room_code', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('invoice.contract', function ($contract) use ($keyword) {
                        $contract->where('contract_code', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('invoice.contract.tenant', function ($tenant) use ($keyword) {
                        $tenant->where('full_name', 'like', "%{$keyword}%");
                    });
            });
        }

        $payments = $query->paginate(10)->withQueryString();

        return view('admin.invoices.payments-export', compact(
            'payments'
        ));
    }

    /**
     * Xuất danh sách thanh toán ra CSV
     */
    public function exportPayments(Request $request)
    {
        $filters = $this->paymentExportFilters($request);
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
        ])->latest('payment_date');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = trim($filters['keyword']);

            $query->where(function ($q) use ($keyword) {
                $q->where('transaction_code', 'like', "%{$keyword}%")
                    ->orWhereHas('invoice.room', function ($room) use ($keyword) {
                        $room->where('room_code', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('invoice.contract', function ($contract) use ($keyword) {
                        $contract->where('contract_code', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('invoice.contract.tenant', function ($tenant) use ($keyword) {
                        $tenant->where('full_name', 'like', "%{$keyword}%");
                    });
            });
        }

        $payments = $query->lazy(500);

        $filename = 'danh_sach_thanh_toan_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Mã giao dịch',
            'Hóa đơn',
            'Phòng',
            'Người thuê',
            'Số tiền',
            'Phương thức',
            'Ngày thanh toán',
            'Trạng thái',
            'Ghi chú',
        ];

        return response()->stream(function () use ($payments, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            Csv::writeRow($file, $columns);

            foreach ($payments as $payment) {
                $methodLabel = match ($payment->payment_method) {
                    Payment::METHOD_BANK_TRANSFER => 'Chuyển khoản',
                    Payment::METHOD_QR => 'QR',
                    default => 'Tiền mặt',
                };

                $statusLabel = match ($payment->status) {
                    Payment::STATUS_SUCCESS => 'Thành công',
                    Payment::STATUS_PENDING => 'Chờ xử lý',
                    default => 'Thất bại',
                };

                Csv::writeRow($file, [
                    $payment->transaction_code ?? '-',
                    $payment->invoice->invoice_code ?? '-',
                    $payment->invoice->room->room_code ?? '-',
                    $payment->invoice->contract->tenant->full_name ?? '-',
                    number_format($payment->amount_paid, 0, ',', '.'),
                    $methodLabel,
                    $payment->payment_date?->format('d/m/Y') ?? '-',
                    $statusLabel,
                    $payment->note ?? '-',
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Ghi nhận thanh toán
     */
    public function storePayment(
        Request $request,
        Invoice $invoice
    ) {

        $data = $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date|before_or_equal:today',
            'payment_method' => 'required|in:'
                .Payment::METHOD_CASH.','
                .Payment::METHOD_BANK_TRANSFER.','
                .Payment::METHOD_QR,
            'transaction_code' => ['nullable', 'string', 'max:255', Rule::unique('payments', 'transaction_code')],
            'note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($data, $invoice) {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $remainingAmount = $lockedInvoice->remaining_amount;

            if ($remainingAmount <= 0 || (float) $data['amount_paid'] > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Số tiền thanh toán vượt quá số tiền còn phải trả của hóa đơn.',
                ]);
            }

            Payment::create([
                'invoice_id' => $lockedInvoice->id,
                'amount_paid' => $data['amount_paid'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'transaction_code' => $data['transaction_code'] ?? null,
                'status' => Payment::STATUS_SUCCESS,
                'confirmed_by' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);

            $lockedInvoice->refreshStatus();
            $this->syncTenantAccountAfterPayment($lockedInvoice);
        });

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'success',
                'Thanh toán thành công.'
            );
    }

    public function approvePayment(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if (! $payment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Xác nhận thanh toán này đã được xử lý trước đó.',
                ]);
            }

            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $remainingAmount = $invoice->remaining_amount;

            if ((float) $payment->amount_paid > $remainingAmount) {
                throw ValidationException::withMessages([
                    'payment' => 'Số tiền xác nhận vượt quá số tiền còn phải trả của hóa đơn.',
                ]);
            }

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'confirmed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => null,
            ]);

            $invoice->refreshStatus();
            $this->syncTenantAccountAfterPayment($invoice);
        });

        return back()->with('success', 'Đã duyệt xác nhận thanh toán.');
    }

    public function rejectPayment(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'review_note' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($data, $payment) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $lockedPayment->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Xác nhận thanh toán này đã được xử lý trước đó.',
                ]);
            }

            $lockedPayment->update([
                'status' => Payment::STATUS_FAILED,
                'confirmed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => $data['review_note'],
            ]);
        });

        return back()->with('success', 'Đã từ chối xác nhận thanh toán.');
    }

    private function syncTenantAccountAfterPayment(Invoice $invoice): void
    {
        $tenant = $invoice->contract()->with('tenant.user')->first()?->tenant;

        if ($tenant) {
            app(TenantAccountLifecycle::class)->sync($tenant);
        }
    }

    private function invoiceExportFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', Rule::in([Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID])],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function paymentExportFilters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_SUCCESS, Payment::STATUS_FAILED])],
            'method' => ['nullable', Rule::in([Payment::METHOD_CASH, Payment::METHOD_BANK_TRANSFER, Payment::METHOD_QR])],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * In hóa đơn
     */
    public function print(Invoice $invoice)
    {
        $invoice->load([

            'contract.tenant',

            'room',

            'utilityReading',

            'details',

            'payments',

        ]);

        return view(
            'admin.invoices.print',
            compact('invoice')
        );
    }
}
