<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    /**
     * Danh sách hóa đơn
     */
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? (int) $request->month
            : null;

        $year = $request->filled('year')
            ? (int) $request->year
            : null;

        $status = $request->status;

        $keyword = trim($request->keyword ?? '');

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

        $invoices = $query
            ->paginate(15)
            ->withQueryString();

        $summaryQuery = Invoice::query();

        if ($month) {
            $summaryQuery->where('month', $month);
        }

        if ($year) {
            $summaryQuery->where('year', $year);
        }

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
        $month = (int) $request->input(
            'month',
            now()->month
        );

        $year = (int) $request->input(
            'year',
            now()->year
        );

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
            ->whereDate(
                'end_date',
                '>=',
                $periodStart
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
        $month = $request->filled('month')
            ? (int) $request->month
            : null;

        $year = $request->filled('year')
            ? (int) $request->year
            : null;

        $status = $request->status;
        $keyword = trim($request->keyword ?? '');

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
                'tenant_name' => $preview['tenant']->full_name ?? 'N/A',
                'month' => $preview['month'],
                'year' => $preview['year'],
                'invoice_date' => $preview['invoice_date'],
                'due_date' => $preview['due_date'],
                'total_amount' => $preview['total_amount'],
                'lines' => $preview['lines'],
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

        $data = $request->validate([

            'status' => 'required|in:'
                .Invoice::STATUS_UNPAID.','
                .Invoice::STATUS_PARTIAL.','
                .Invoice::STATUS_PAID,

        ]);

        $invoice->update([
            'status' => $data['status'],
        ]);

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'success',
                'Cập nhật trạng thái hóa đơn thành công.'
            );
    }

    /**
     * Xóa hóa đơn
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {

            return redirect()
                ->route('admin.invoices.index')
                ->with(
                    'error',
                    'Không thể xóa hóa đơn đã phát sinh thanh toán.'
                );
        }

        DB::transaction(function () use ($invoice) {

            $invoice->details()->delete();

            $invoice->delete();
        });

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
        $month = $request->filled('month')
            ? (int) $request->month
            : null;

        $year = $request->filled('year')
            ? (int) $request->year
            : null;

        $status = $request->status;

        $keyword = trim($request->keyword ?? '');

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

        $invoices = $query->get();

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

        $file = fopen('php://temp', 'w+');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, $columns);

        foreach ($invoices as $invoice) {
            $paidAmount = $invoice->paid_amount ?? $invoice->payments()->success()->sum('amount_paid');
            $remainingAmount = max(0, $invoice->total_amount - $paidAmount);

            fputcsv($file, [
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

        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        return response($csv, 200, $headers);
    }

    /**
     * Danh sách thanh toán
     */
    public function payments(Request $request)
    {
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
        ])->latest('payment_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where(
                'payment_method',
                $request->input('method')
            );
        }

        if ($request->filled('keyword')) {

            $keyword = trim($request->keyword);

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
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
        ])->latest('payment_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where(
                'payment_method',
                $request->input('method')
            );
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

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
        $query = Payment::with([
            'invoice.contract.tenant',
            'invoice.room',
            'confirmer',
        ])->latest('payment_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->input('method'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

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

        $payments = $query->get();

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

        $file = fopen('php://temp', 'w+');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($file, $columns);

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

            fputcsv($file, [
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

        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        return response($csv, 200, $headers);
    }

    /**
     * Ghi nhận thanh toán
     */
    public function storePayment(
        Request $request,
        Invoice $invoice
    ) {

        $remainingAmount = $invoice->remaining_amount;

        if ($remainingAmount <= 0) {
            return back()->with('error', 'Hóa đơn đã được thanh toán đủ.');
        }

        $data = $request->validate([

            'amount_paid' => 'required|numeric|min:1|max:'.$remainingAmount,

            'payment_date' => 'required|date',

            'payment_method' => 'required|in:'
                .Payment::METHOD_CASH.','
                .Payment::METHOD_BANK_TRANSFER.','
                .Payment::METHOD_QR,

            'transaction_code' => 'nullable|max:255',

            'note' => 'nullable|string',

        ]);

        Payment::create([

            'invoice_id' => $invoice->id,

            'amount_paid' => $data['amount_paid'],

            'payment_date' => $data['payment_date'],

            'payment_method' => $data['payment_method'],

            'transaction_code' => $data['transaction_code'] ?? null,

            'status' => Payment::STATUS_SUCCESS,

            'confirmed_by' => auth()->id(),

            'note' => $data['note'] ?? null,

        ]);

        $invoice->refreshStatus();

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
