<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractCredit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\UtilityReading;
use App\Services\AdminNotificationService;
use App\Services\ClientNotificationService;
use App\Services\ContractLifecycleService;
use App\Services\InvoiceGenerator;
use App\Services\SettlementService;
use App\Services\TenantAccountLifecycle;
use App\Support\Csv;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            'status' => ['nullable', 'in:unpaid,partial,paid,written_off,cancelled'],
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
            Invoice::STATUS_WRITTEN_OFF,
            Invoice::STATUS_CANCELLED,
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
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->sum(DB::raw('total_amount + adjustment_amount')),

            'unpaid' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_UNPAID)
                ->count(),

            'partial' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_PARTIAL)
                ->count(),

            'paid' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_PAID)
                ->count(),

            'cancelled' => (clone $summaryQuery)
                ->where('status', Invoice::STATUS_CANCELLED)
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
            'adjustments.creator',
            'parentInvoice',
            'supplementalInvoices',
            'creditsCreated.creator',
            'creditsCreated.applications.invoice',
            'creditApplications.credit',
            'canceller',
            'issuer',
        ]);

        $paidAmount = $invoice->payments()
            ->success()
            ->sum('amount_paid');

        $remainingAmount = (float) $invoice->remaining_amount;
        $pendingAmount = (float) $invoice->payments()
            ->pending()
            ->sum('amount_paid');
        $availableAmount = max(0, $remainingAmount - $pendingAmount);

        return view(
            'admin.invoices.show',
            compact(
                'invoice',
                'paidAmount',
                'remainingAmount',
                'pendingAmount',
                'availableAmount'
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
        $servicePeriodStart = $periodStart->copy()->subMonthNoOverflow()->startOfMonth();

        $contracts = Contract::with([
            'room',
            'tenant',
        ])
            ->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED, Contract::STATUS_SETTLING])
            ->whereDate(
                'start_date',
                '<',
                $periodStart
            )
            // Kỳ hiệu lực kết thúc ưu tiên: actual_end_date > extend_end_date > end_date
            ->where(function ($query) use ($servicePeriodStart) {
                $query->where('status', Contract::STATUS_EXPIRED)
                    ->orWhereRaw('COALESCE(actual_end_date, extend_end_date, end_date) >= ?', [$servicePeriodStart->toDateString()]);
            })
            ->orderBy('id')
            ->get();

        $issuedContractIds = Invoice::where('month', $month)
            ->where('year', $year)
            ->where('invoice_type', Invoice::TYPE_RENTAL)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->pluck('contract_id')
            ->toArray();

        $setting = Setting::currentOrCreate();
        $scheduledInvoiceDate = $periodStart->copy()->day(
            max(1, min((int) $setting->invoice_day, $periodStart->daysInMonth))
        );
        $canIssue = ! today()->lt($scheduledInvoiceDate);

        return view(
            'admin.invoices.generate',
            compact(
                'contracts',
                'month',
                'year',
                'years',
                'issuedContractIds',
                'scheduledInvoiceDate',
                'canIssue'
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
                (int) $data['year'],
                $request->user()?->id,
            );
            app(ClientNotificationService::class)->invoice($invoice, 'invoice_issued', 'Có hóa đơn mới', 'Hóa đơn '.$invoice->invoice_code.' đã được phát hành. Hạn thanh toán: '.$invoice->due_date?->format('d/m/Y').'.');
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
            Invoice::STATUS_CANCELLED,
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
                'issued_at' => now()->format('H:i d/m/Y'),
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
                (int) $data['year'],
                $request->user()?->id,
            );

            app(ClientNotificationService::class)->invoice($invoice, 'invoice_issued', 'Có hóa đơn mới', 'Hóa đơn '.$invoice->invoice_code.' đã được phát hành. Hạn thanh toán: '.$invoice->due_date?->format('d/m/Y').'.');

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

    public function cancel(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($invoice, $data): void {
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($lockedInvoice->payments()->exists()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Không thể hủy hóa đơn đã phát sinh thanh toán hoặc xác nhận thanh toán.',
                ]);
            }

            if ($lockedInvoice->adjustments()->exists()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Không thể hủy hóa đơn đã có phiếu điều chỉnh.',
                ]);
            }

            if ($lockedInvoice->supplementalInvoices()->where('status', '!=', Invoice::STATUS_CANCELLED)->exists()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Không thể hủy hóa đơn gốc đang có hóa đơn bổ sung còn hiệu lực.',
                ]);
            }

            if ($lockedInvoice->creditsCreated()->exists()) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Không thể hủy hóa đơn đã tạo khoản giảm cho kỳ sau.',
                ]);
            }

            if ($lockedInvoice->status !== Invoice::STATUS_UNPAID) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Chỉ hóa đơn chưa thanh toán mới có thể hủy.',
                ]);
            }

            $reading = $lockedInvoice->utilityReading()
                ->lockForUpdate()
                ->first();

            $creditApplications = $lockedInvoice->creditApplications()
                ->with('credit')
                ->lockForUpdate()
                ->get();
            foreach ($creditApplications as $application) {
                $credit = ContractCredit::query()->lockForUpdate()->findOrFail($application->contract_credit_id);
                $credit->increment('remaining_amount', $application->amount);
                $application->delete();
            }

            $lockedInvoice->update([
                'status' => Invoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $data['cancellation_reason'],
            ]);

            if ($reading && ! $reading->invoice()->exists()) {
                $reading->update(['status' => UtilityReading::STATUS_CONFIRMED]);
            }
        });

        app(ClientNotificationService::class)->invoice($invoice->fresh(), 'invoice_cancelled', 'Hóa đơn đã bị hủy', 'Hóa đơn '.$invoice->invoice_code.' đã bị hủy. Lý do: '.$data['cancellation_reason']);

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Đã hủy hóa đơn. Dữ liệu gốc được giữ lại để truy vết.');
    }

    public function storeAdjustment(Request $request, Invoice $invoice)
    {
        throw ValidationException::withMessages([
            'direction' => 'Điều chỉnh trực tiếp đã ngừng sử dụng. Hãy tạo hóa đơn bổ sung nếu cần thu thêm hoặc ghi khoản giảm cho hóa đơn tháng sau.',
        ]);
    }

    public function storeSupplemental(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['utility', 'service', 'parking', 'damage', 'other'])],
            'description' => ['required', 'string', 'min:5', 'max:500'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999999.99'],
        ]);

        $supplemental = DB::transaction(function () use ($invoice, $data): Invoice {
            $source = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($source->invoice_type !== Invoice::TYPE_RENTAL) {
                throw ValidationException::withMessages([
                    'amount' => 'Chỉ được tạo hóa đơn bổ sung từ hóa đơn tiền phòng và tiện ích hằng tháng.',
                ]);
            }
            if (in_array($source->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_WRITTEN_OFF], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Không thể tạo hóa đơn bổ sung từ hóa đơn đã hủy hoặc đã xóa nợ.',
                ]);
            }

            $revision = ((int) Invoice::query()
                ->where('contract_id', $source->contract_id)
                ->where('invoice_type', Invoice::TYPE_SUPPLEMENTAL)
                ->where('month', $source->month)
                ->where('year', $source->year)
                ->max('revision')) + 1;
            $setting = Setting::currentOrCreate();
            $amount = round((float) $data['amount']);
            $categoryLabel = match ($data['category']) {
                'utility' => 'Truy thu điện, nước',
                'service' => 'Phí dịch vụ phát sinh',
                'parking' => 'Phí gửi xe phát sinh',
                'damage' => 'Bồi thường hư hỏng',
                default => 'Chi phí phát sinh khác',
            };

            $supplemental = Invoice::create([
                'contract_id' => $source->contract_id,
                'parent_invoice_id' => $source->id,
                'invoice_type' => Invoice::TYPE_SUPPLEMENTAL,
                'revision' => $revision,
                'room_id' => $source->room_id,
                'invoice_code' => null,
                'month' => $source->month,
                'year' => $source->year,
                'invoice_date' => today()->toDateString(),
                'issued_at' => now(),
                'issued_by' => auth()->id(),
                'due_date' => today()->addDays((int) $setting->payment_due_days)->toDateString(),
                'room_fee' => 0,
                'electricity_fee' => 0,
                'water_fee' => 0,
                'internet_fee' => 0,
                'service_fee' => 0,
                'total_amount' => $amount,
                'status' => Invoice::STATUS_UNPAID,
            ]);
            $supplemental->update([
                'invoice_code' => sprintf('SUP-%04d%02d-%06d', $source->year, $source->month, $supplemental->id),
            ]);
            $supplemental->details()->create([
                'type' => 'supplemental_'.$data['category'],
                'name' => $categoryLabel,
                'quantity' => 1,
                'unit' => 'lần',
                'unit_price' => $amount,
                'amount' => $amount,
                'note' => $data['description'],
                'sort_order' => 1,
            ]);

            return $supplemental;
        });

        app(ClientNotificationService::class)->invoice(
            $supplemental,
            'supplemental_invoice_issued',
            'Có hóa đơn bổ sung mới',
            'Hóa đơn '.$supplemental->invoice_code.' đã được phát hành từ hóa đơn '.$invoice->invoice_code.'.'
        );

        return redirect()->route('admin.invoices.show', $supplemental)
            ->with('success', 'Đã phát hành hóa đơn bổ sung độc lập.');
    }

    public function storeNextInvoiceCredit(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999999.99'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $credit = DB::transaction(function () use ($invoice, $data): ContractCredit {
            $source = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($source->invoice_type !== Invoice::TYPE_RENTAL) {
                throw ValidationException::withMessages([
                    'amount' => 'Chỉ được tạo khoản giảm từ hóa đơn tiền phòng và tiện ích hằng tháng.',
                ]);
            }
            if (in_array($source->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_WRITTEN_OFF], true)) {
                throw ValidationException::withMessages([
                    'amount' => 'Không thể tạo khoản giảm từ hóa đơn đã hủy hoặc đã xóa nợ.',
                ]);
            }

            $amount = round((float) $data['amount']);
            $credit = ContractCredit::create([
                'contract_id' => $source->contract_id,
                'source_invoice_id' => $source->id,
                'credit_code' => null,
                'amount' => $amount,
                'remaining_amount' => $amount,
                'reason' => $data['reason'],
                'created_by' => auth()->id(),
            ]);
            $credit->update(['credit_code' => sprintf('CRD-%06d', $credit->id)]);

            return $credit;
        });

        app(ClientNotificationService::class)->invoice(
            $invoice,
            'invoice_credit_created',
            'Đã ghi nhận khoản giảm',
            'Khoản giảm '.$credit->credit_code.' sẽ được tự động trừ vào hóa đơn tháng kế tiếp.'
        );

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Đã ghi nhận khoản giảm; hệ thống sẽ tự khấu trừ vào hóa đơn tháng kế tiếp.');
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
            Invoice::STATUS_CANCELLED,
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
                $remainingAmount = (float) $invoice->remaining_amount;

                Csv::writeRow($file, [
                    $invoice->invoice_code,
                    sprintf('%02d', $invoice->month).'/'.$invoice->year,
                    $invoice->room->room_code ?? '-',
                    $invoice->contract->tenant->full_name ?? '-',
                    number_format($invoice->payable_amount, 0, ',', '.'),
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
        ])
            ->latest('created_at')
            ->latest('id');

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
                .Payment::METHOD_BANK_TRANSFER,
            'proof_image' => [
                'nullable',
                'required_if:payment_method,'.Payment::METHOD_BANK_TRANSFER,
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'note' => 'nullable|string|max:1000',
        ], [
            'amount_paid.required' => 'Vui lòng nhập số tiền đã thu.',
            'amount_paid.numeric' => 'Số tiền đã thu không hợp lệ.',
            'amount_paid.min' => 'Số tiền đã thu phải lớn hơn 0.',
            'payment_date.required' => 'Vui lòng chọn ngày thu tiền.',
            'payment_date.date' => 'Ngày thu tiền không hợp lệ.',
            'payment_date.before_or_equal' => 'Ngày thu tiền không được ở tương lai.',
            'payment_method.required' => 'Vui lòng chọn hình thức thanh toán.',
            'payment_method.in' => 'Hình thức thanh toán không hợp lệ.',
            'proof_image.required_if' => 'Vui lòng tải ảnh minh chứng khi thu bằng chuyển khoản.',
            'proof_image.image' => 'Minh chứng thanh toán phải là hình ảnh hợp lệ.',
            'proof_image.mimes' => 'Minh chứng chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'proof_image.max' => 'Ảnh minh chứng không được vượt quá 5 MB.',
        ]);

        $proofPath = $request->file('proof_image')?->store('payment-proofs/admin', 'local');
        try {
            DB::transaction(function () use ($data, $invoice, $proofPath) {
                $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
                if (! $lockedInvoice->canPay()) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Hóa đơn không còn nhận thanh toán.',
                    ]);
                }
                $reservedAmount = (float) $lockedInvoice->payments()
                    ->whereIn('status', [Payment::STATUS_SUCCESS, Payment::STATUS_PENDING])
                    ->sum('amount_paid');
                $availableAmount = max(0, $lockedInvoice->payable_amount - $reservedAmount);

                if ($availableAmount <= 0 || (float) $data['amount_paid'] > $availableAmount) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Số tiền thanh toán vượt quá số dư chưa được thanh toán hoặc giữ chỗ.',
                    ]);
                }

                Payment::create([
                    'invoice_id' => $lockedInvoice->id,
                    'amount_paid' => $data['amount_paid'],
                    'payment_date' => $data['payment_date'],
                    'payment_method' => $data['payment_method'],
                    'transaction_code' => null,
                    'proof_image' => $proofPath,
                    'status' => Payment::STATUS_SUCCESS,
                    'submitted_by' => auth()->id(),
                    'confirmed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'note' => $data['note'] ?? null,
                ]);

                $lockedInvoice->refreshStatus();
                if (in_array($lockedInvoice->invoice_type, [Invoice::TYPE_FIRST_MONTH_RENT, Invoice::TYPE_DEPOSIT], true)) {
                    app(ContractLifecycleService::class)->syncDepositState($lockedInvoice->contract, auth()->user());
                }
                $this->syncTenantAccountAfterPayment($lockedInvoice);
            });
        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
            throw $exception;
        }

        if ($request->boolean('return_to_contract')) {
            return redirect()
                ->route('admin.contracts.show', $invoice->contract_id)
                ->with('success', 'Đã ghi nhận thanh toán.');
        }

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

            if (! $invoice->canPay()) {
                throw ValidationException::withMessages([
                    'payment' => 'Hóa đơn không còn nhận thanh toán.',
                ]);
            }

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
            if (in_array($invoice->invoice_type, [Invoice::TYPE_FIRST_MONTH_RENT, Invoice::TYPE_DEPOSIT], true)) {
                app(ContractLifecycleService::class)->syncDepositState($invoice->contract, auth()->user());
            }
            $this->syncTenantAccountAfterPayment($invoice);
            app(AdminNotificationService::class)->resolve('payment_review', $payment);
        });

        $payment->refresh();
        app(ClientNotificationService::class)->payment(
            $payment,
            'payment_approved',
            'Thanh toán đã được xác nhận',
            'Ban quản lý đã xác nhận khoản thanh toán '.number_format((float) $payment->amount_paid, 0, ',', '.').' VNĐ.'
        );

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
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);
            $invoice->refreshStatus();
            if (in_array($invoice->invoice_type, [Invoice::TYPE_FIRST_MONTH_RENT, Invoice::TYPE_DEPOSIT], true)) {
                app(ContractLifecycleService::class)->syncDepositState($invoice->contract, auth()->user(), 'Thanh toán bị từ chối.');
            }
            app(AdminNotificationService::class)->resolve('payment_review', $lockedPayment);
        });

        $payment->refresh();
        app(ClientNotificationService::class)->payment(
            $payment,
            'payment_rejected',
            'Xác nhận thanh toán bị từ chối',
            'Khoản thanh toán chưa được chấp thuận. Lý do: '.$data['review_note']
        );

        return back()->with('success', 'Đã từ chối xác nhận thanh toán.');
    }

    public function paymentProof(Payment $payment): BinaryFileResponse
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

    private function syncTenantAccountAfterPayment(Invoice $invoice): void
    {
        $contract = $invoice->contract()->with(['tenant.user', 'settlementStatement'])->first();
        $tenant = $contract?->tenant;

        if ($contract?->status === Contract::STATUS_SETTLING
            && $contract->settlementStatement) {
            app(SettlementService::class)
                ->refreshFinancials($contract->settlementStatement);
        }

        if ($tenant) {
            app(TenantAccountLifecycle::class)->sync($tenant);
        }
    }

    private function invoiceExportFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'status' => ['nullable', Rule::in([
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_PARTIAL,
                Invoice::STATUS_PAID,
                Invoice::STATUS_CANCELLED,
            ])],
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

            'adjustments',

            'payments',

        ]);

        return view(
            'admin.invoices.print',
            compact('invoice')
        );
    }
}
