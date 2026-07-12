<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Role;
use App\Services\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OverviewController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? (int) $request->month
            : null;

        $year = $request->filled('year')
            ? (int) $request->year
            : null;

        $status = $request->status;

        $keyword = trim((string) $request->keyword);

        $query = Invoice::with([
            'contract.tenant',
            'room',
            'payments',
        ])
            ->withSum([
                'payments as paid_amount' => function ($q) {
                    $q->success();
                }
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

                $q->where('invoice_code', 'like', "%{$keyword}%")

                    ->orWhereHas('room', function ($room) use ($keyword) {

                        $room->where(
                            'room_code',
                            'like',
                            "%{$keyword}%"
                        );
                    })

                    ->orWhereHas('contract', function ($contract) use ($keyword) {

                        $contract->where(
                            'contract_code',
                            'like',
                            "%{$keyword}%"
                        )

                            ->orWhereHas('tenant', function ($tenant) use ($keyword) {

                                $tenant->where(
                                    'full_name',
                                    'like',
                                    "%{$keyword}%"
                                )

                                    ->orWhere(
                                        'phone',
                                        'like',
                                        "%{$keyword}%"
                                    );
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
                ->where(
                    'status',
                    Invoice::STATUS_UNPAID
                )->count(),

            'partial' => (clone $summaryQuery)
                ->where(
                    'status',
                    Invoice::STATUS_PARTIAL
                )->count(),

            'paid' => (clone $summaryQuery)
                ->where(
                    'status',
                    Invoice::STATUS_PAID
                )->count(),
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
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'contract.tenant',
            'room',
            'utilityReading',
            'details',
            'payments',
        ]);

        $paidAmount = $invoice->payments
            ->where('status', Payment::STATUS_SUCCESS)
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
     * Form sinh hóa đơn
     */
    public function generate(Request $request)
    {
        $month = (int) $request->input(
            'month',
            Carbon::now()->month
        );

        $year = (int) $request->input(
            'year',
            Carbon::now()->year
        );

        $periodStart = Carbon::createFromDate(
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
            ->active()
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
            ->orderBy('room_id')
            ->get();

        $issuedRoomIds = Invoice::where(
            'month',
            $month
        )
            ->where(
                'year',
                $year
            )
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->all();

        return view(
            'admin.invoices.generate',
            compact(
                'contracts',
                'month',
                'year',
                'issuedRoomIds'
            )
        );
    }

    /**
     * Preview hóa đơn trước khi phát hành
     */
    public function preview(
        Request $request,
        Contract $contract,
        InvoiceGenerator $generator
    ): JsonResponse {

        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000|max:2100',
        ]);

        try {

            $preview = $generator->preview(
                $contract,
                (int) $data['month'],
                (int) $data['year']
            );
        } catch (ValidationException $e) {

            return response()->json([
                'message' => collect(
                    $e->errors()
                )->flatten()->first(),
            ], 422);
        }

        return response()->json([

            'contract_id' => $preview['contract']->id,

            'contract_code' => $preview['contract']->contract_code,

            'room_code' => $preview['room']->room_code,

            'tenant_name' =>
            $preview['tenant']->full_name
                ?? $preview['tenant']->name
                ?? 'N/A',

            'month' => $preview['month'],

            'year' => $preview['year'],

            'invoice_date' => $preview['invoice_date'],

            'due_date' => $preview['due_date'],

            'total_amount' => $preview['total_amount'],

            'lines' => $preview['lines'],
        ]);
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
            'year'  => 'required|integer|min:2000|max:2100',
        ]);

        try {

            $invoice = $generator->issue(
                $contract,
                (int) $data['month'],
                (int) $data['year']
            );
        } catch (ValidationException $e) {

            return response()->json([
                'message' => collect(
                    $e->errors()
                )->flatten()->first(),
            ], 422);
        } catch (QueryException $e) {

            return response()->json([
                'message' => 'Hóa đơn tháng này đã tồn tại hoặc dữ liệu không hợp lệ.',
            ], 422);
        }

        return response()->json([
            'message'      => 'Đã phát hành hóa đơn thành công.',
            'invoice_id'   => $invoice->id,
            'total_amount' => $invoice->total_amount,
        ]);
    }

    /**
     * Danh sách hóa đơn cần thanh toán
     */
    public function payments()
    {
        $invoices = Invoice::with([
            'contract.room',
            'contract.tenant',
        ])
            ->whereIn('status', [
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_PARTIAL,
            ])
            ->latest('invoice_date')
            ->paginate(15);

        return view(
            'admin.invoices.payments',
            compact('invoices')
        );
    }

    /**
     * Ghi nhận thanh toán
     */
    public function storePayment(
        Request $request,
        Invoice $invoice
    ) {

        $data = $request->validate([
            'amount_paid'      => 'required|numeric|min:0.01',
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|in:cash,bank_transfer,qr',
            'transaction_code' => 'nullable|string|max:100',
            'note'             => 'nullable|string',
        ]);

        DB::transaction(function () use (
            $invoice,
            $data
        ) {

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

            $paidAmount = $invoice->payments()
                ->success()
                ->sum('amount_paid');

            if ($paidAmount >= $invoice->total_amount) {

                $invoice->status = Invoice::STATUS_PAID;
            } elseif ($paidAmount > 0) {

                $invoice->status = Invoice::STATUS_PARTIAL;
            } else {

                $invoice->status = Invoice::STATUS_UNPAID;
            }

            $invoice->save();
        });

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'success',
                'Đã ghi nhận thanh toán thành công.'
            );
    }
    /**
     * In hóa đơn
     */
    public function print(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenant',
            'utilityReading',
            'details',
            'payments',
        ]);

        return view(
            'admin.invoices.print',
            compact('invoice')
        );
    }

    /**
     * Form sửa hóa đơn
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load([
            'contract.room',
            'contract.tenant',
            'utilityReading',
            'details',
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
                . Invoice::STATUS_UNPAID . ','
                . Invoice::STATUS_PARTIAL . ','
                . Invoice::STATUS_PAID,

            'due_date' => 'nullable|date',
        ]);

        $invoice->update($data);

        return redirect()
            ->route(
                'admin.invoices.show',
                $invoice
            )
            ->with(
                'success',
                'Cập nhật hóa đơn thành công.'
            );
    }

    /**
     * Xóa hóa đơn
     */
    public function destroy(Invoice $invoice)
    {
        if (!$invoice->isUnpaid()) {

            return redirect()
                ->route('admin.invoices.index')
                ->with(
                    'error',
                    'Chỉ được xóa hóa đơn chưa thanh toán.'
                );
        }

        DB::transaction(function () use ($invoice) {

            $invoice->details()->delete();

            $invoice->payments()->delete();

            $invoice->delete();
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with(
                'success',
                'Đã xóa hóa đơn thành công.'
            );
    }
    public function fillRate()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRooms = Room::count();

        $occupiedRooms = Room::occupied()->count();

        $availableRooms = Room::available()->count();

        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent = $totalRooms > 0
            ? round(($occupiedRooms / $totalRooms) * 100, 1)
            : 0;

        $availablePercent = $totalRooms > 0
            ? round(($availableRooms / $totalRooms) * 100, 1)
            : 0;

        $maintenancePercent = $totalRooms > 0
            ? round(($maintenanceRooms / $totalRooms) * 100, 1)
            : 0;

        return view(
            'admin.overview.fill-rate',
            compact(
                'occupiedPercent',
                'availablePercent',
                'maintenancePercent',
                'totalRooms',
                'occupiedRooms',
                'availableRooms',
                'maintenanceRooms'
            )
        );
    }

    public function revenueChart()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $currentYear = Carbon::now()->year;
        $monthlyRevenue = [];

        // Get revenue for each month
        for ($month = 1; $month <= 12; $month++) {
            $revenue = Invoice::where('year', $currentYear)
                ->where('month', $month)
                ->sum('total_amount');
            $monthlyRevenue[] = $revenue;
        }

        // Get yearly revenue for last 5 years
        $yearlyRevenue = [];
        $yearLabels = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $revenue = Invoice::where('year', $year)
                ->sum('total_amount');
            $yearlyRevenue[] = $revenue;
            $yearLabels[] = (string)$year;
        }

        return view('admin.overview.revenue-chart', compact('currentYear', 'monthlyRevenue', 'yearlyRevenue', 'yearLabels'));
    }

    public function revenueStats()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRevenue = Invoice::sum('total_amount');
        $monthRevenue = Invoice::where('month', Carbon::now()->month)
            ->where('year', Carbon::now()->year)
            ->sum('total_amount');
        $todayRevenue = Invoice::whereDate('created_at', Carbon::today())
            ->sum('total_amount');
        $totalBilled = Invoice::sum('total_amount');
        $totalReceivable = Invoice::sum('total_amount') - (Payment::sum('amount_paid') ?? 0);
        
        // Calculate collection rate
        $totalPaid = Payment::sum('amount_paid') ?? 0;
        $collectionRate = $totalBilled > 0 
            ? round(($totalPaid / $totalBilled) * 100, 1)
            : 0;

        return view('admin.overview.revenue-stats', compact(
            'totalRevenue',
            'monthRevenue',
            'todayRevenue',
            'totalBilled',
            'totalReceivable',
            'collectionRate'
        ));
    }

    public function roomStats()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRooms = Room::count();
        $occupiedRooms = Room::occupied()->count();
        $availableRooms = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        return view('admin.overview.room-stats', compact(
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms'
        ));
    }
}
