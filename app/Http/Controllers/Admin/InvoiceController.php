<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Services\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month') ? (int) $request->input('month') : null;
        $year = $request->filled('year') ? (int) $request->input('year') : null;
        $status = $request->input('status');
        $keyword = trim((string) $request->input('keyword'));

        $query = Invoice::with(['contract.tenant', 'room', 'payments'])
            ->withSum(['payments as paid_amount' => function ($query) {
                $query->where('status', 'success');
            }], 'amount_paid')
            ->latest('invoice_date')
            ->latest('id');

        if ($month) {
            $query->where('month', $month);
        }

        if ($year) {
            $query->where('year', $year);
        }

        if (in_array($status, ['unpaid', 'partial', 'paid'], true)) {
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->where('id', $keyword)
                    ->orWhereHas('room', function ($roomQuery) use ($keyword) {
                        $roomQuery->where('room_code', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('contract', function ($contractQuery) use ($keyword) {
                        $contractQuery->where('contract_code', 'like', "%{$keyword}%")
                            ->orWhereHas('tenant', function ($tenantQuery) use ($keyword) {
                                $tenantQuery->where('full_name', 'like', "%{$keyword}%")
                                    ->orWhere('phone', 'like', "%{$keyword}%");
                            });
                    });
            });
        }

        $invoices = $query->paginate(15)->withQueryString();

        $summaryQuery = Invoice::query();

        if ($month) {
            $summaryQuery->where('month', $month);
        }

        if ($year) {
            $summaryQuery->where('year', $year);
        }

        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'total_amount' => (clone $summaryQuery)->sum('total_amount'),
            'unpaid' => (clone $summaryQuery)->where('status', 'unpaid')->count(),
            'partial' => (clone $summaryQuery)->where('status', 'partial')->count(),
            'paid' => (clone $summaryQuery)->where('status', 'paid')->count(),
        ];

        return view('admin.invoices.index', compact('invoices', 'month', 'year', 'status', 'keyword', 'summary'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['contract.tenant', 'room', 'utilityReading', 'details', 'payments']);

        $paidAmount = $invoice->payments
            ->where('status', 'success')
            ->sum('amount_paid');

        $remainingAmount = max(0, $invoice->total_amount - $paidAmount);

        return view('admin.invoices.show', compact('invoice', 'paidAmount', 'remainingAmount'));
    }

    public function generate(Request $request)
    {
        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $contracts = Contract::with(['room', 'tenant'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $periodEnd)
            ->whereDate('end_date', '>=', $periodStart)
            ->orderBy('room_id')
            ->get();

        $issuedRoomIds = Invoice::where('month', $month)
            ->where('year', $year)
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->all();

        return view('admin.invoices.generate', compact('contracts', 'month', 'year', 'issuedRoomIds'));
    }

    public function preview(Request $request, Contract $contract, InvoiceGenerator $generator): JsonResponse
    {
        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $preview = $generator->preview($contract, (int) $data['month'], (int) $data['year']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first(),
            ], 422);
        }

        return response()->json([
            'contract_id' => $preview['contract']->id,
            'contract_code' => $preview['contract']->contract_code,
            'room_code' => $preview['room']->room_code,
            'tenant_name' => $preview['tenant']->full_name ?? $preview['tenant']->name ?? 'N/A',
            'month' => $preview['month'],
            'year' => $preview['year'],
            'invoice_date' => $preview['invoice_date'],
            'due_date' => $preview['due_date'],
            'total_amount' => $preview['total_amount'],
            'lines' => $preview['lines'],
        ]);
    }

    public function issue(Request $request, Contract $contract, InvoiceGenerator $generator): JsonResponse
    {
        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $invoice = $generator->issue($contract, (int) $data['month'], (int) $data['year']);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first(),
            ], 422);
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Hóa đơn tháng này đã tồn tại hoặc dữ liệu không hợp lệ.',
            ], 422);
        }

        return response()->json([
            'message' => 'Đã phát hành hóa đơn thành công.',
            'invoice_id' => $invoice->id,
            'total_amount' => $invoice->total_amount,
        ]);
    }
}
