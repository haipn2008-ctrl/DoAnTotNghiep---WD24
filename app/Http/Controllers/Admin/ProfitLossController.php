<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfitLossController extends Controller
{
    public function index(Request $request): View
    {
        $currentYear = now()->year;
        $filters = $request->validate([
            'year' => 'nullable|integer|between:2000,2100',
            'month' => 'nullable|integer|between:1,12',
            'gov_electricity_unit_price' => 'nullable|numeric|min:0',
            'gov_water_unit_price' => 'nullable|numeric|min:0',
        ]);

        $selectedYear = (int) ($filters['year'] ?? $currentYear);
        $selectedMonth = isset($filters['month']) ? (int) $filters['month'] : null;
        $selectedUtilityMonth = $selectedMonth ?: (int) now()->month;
        $selectedUtilityYear = $selectedYear;

        // 1. Tổng quan Doanh thu (Thu) vs Chi phí (Chi)
        $revenueQuery = Payment::success()->whereYear('payment_date', $selectedYear);
        $expenseQuery = Expense::query()->whereYear('expense_date', $selectedYear);

        if ($selectedMonth) {
            $revenueQuery->whereMonth('payment_date', $selectedMonth);
            $expenseQuery->whereMonth('expense_date', $selectedMonth);
        }

        $totalRevenue = (float) (clone $revenueQuery)->sum('amount_paid');
        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $netProfit = $totalRevenue - $totalExpenses;
        $profitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        $revenueDetails = (clone $revenueQuery)
            ->with(['invoice:id,invoice_code,room_id', 'invoice.room:id,room_code'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->take(12)
            ->get(['id', 'invoice_id', 'transaction_code', 'amount_paid', 'payment_date']);
        $revenueDetailsCount = (clone $revenueQuery)->count();

        $expenseDetails = (clone $expenseQuery)
            ->with('room:id,room_code')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->take(12)
            ->get(['id', 'expense_code', 'title', 'category', 'amount', 'expense_date', 'room_id']);
        $expenseDetailsCount = (clone $expenseQuery)->count();
        $expenseCategoryLabels = Expense::categories();

        // 2. Đối soát Điện - Nước (Thu từ khách vs Đơn giá NN dự kiến vs Đã đóng thực tế)
        $invoiceUtilityQuery = Invoice::query()
            ->whereYear('invoice_date', $selectedYear)
            ->where('status', '!=', Invoice::STATUS_CANCELLED);

        if ($selectedMonth) {
            $invoiceUtilityQuery->whereMonth('invoice_date', $selectedMonth);
        }

        $utilityInvoiced = $invoiceUtilityQuery->selectRaw('
            COALESCE(SUM(electricity_fee), 0) as elec_invoiced,
            COALESCE(SUM(water_fee), 0) as water_invoiced
        ')->first();

        $elecInvoiced = (float) ($utilityInvoiced->elec_invoiced ?? 0);
        $waterInvoiced = (float) ($utilityInvoiced->water_invoiced ?? 0);

        $utilityUsageSummary = Invoice::query()
            ->join('utility_readings', 'utility_readings.id', '=', 'invoices.utility_reading_id')
            ->whereYear('invoices.invoice_date', $selectedUtilityYear)
            ->whereMonth('invoices.invoice_date', $selectedUtilityMonth)
            ->where('invoices.status', '!=', Invoice::STATUS_CANCELLED)
            ->selectRaw('COALESCE(SUM(utility_readings.electricity_new - utility_readings.electricity_old), 0) as electricity_usage, COALESCE(SUM(utility_readings.water_new - utility_readings.water_old), 0) as water_usage')
            ->first();

        $totalElectricityUsage = max(0, (float) ($utilityUsageSummary->electricity_usage ?? 0));
        $totalWaterUsage = max(0, (float) ($utilityUsageSummary->water_usage ?? 0));

        $elecPaidGov = (float) (clone $expenseQuery)->where('category', Expense::CATEGORY_ELECTRICITY)->sum('amount');
        $waterPaidGov = (float) (clone $expenseQuery)->where('category', Expense::CATEGORY_WATER)->sum('amount');

        $suggestedGovElectricityUnitPrice = $totalElectricityUsage > 0
            ? round($elecPaidGov / $totalElectricityUsage, 2)
            : 0;
        $suggestedGovWaterUnitPrice = $totalWaterUsage > 0
            ? round($waterPaidGov / $totalWaterUsage, 2)
            : 0;

        $govElectricityUnitPrice = (float) ($filters['gov_electricity_unit_price'] ?? $suggestedGovElectricityUnitPrice);
        $govWaterUnitPrice = (float) ($filters['gov_water_unit_price'] ?? $suggestedGovWaterUnitPrice);

        $elecGovEstimated = $totalElectricityUsage * $govElectricityUnitPrice;
        $waterGovEstimated = $totalWaterUsage * $govWaterUnitPrice;

        $elecDiff = $elecInvoiced - $elecPaidGov;
        $waterDiff = $waterInvoiced - $waterPaidGov;

        $elecGovBalance = $elecGovEstimated - $elecPaidGov;
        $waterGovBalance = $waterGovEstimated - $waterPaidGov;

        // 3. Biểu đồ 12 tháng trong năm được chọn
        $monthlyRevenueData = [];
        $monthlyExpenseData = [];
        $monthlyProfitData = [];
        $monthsLabels = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];

        $monthRevenueGroup = Payment::success()
            ->whereYear('payment_date', $selectedYear)
            ->selectRaw($this->monthExpression('payment_date') . ' as m, SUM(amount_paid) as total')
            ->groupByRaw($this->monthExpression('payment_date'))
            ->pluck('total', 'm');

        $monthExpenseGroup = Expense::query()
            ->whereYear('expense_date', $selectedYear)
            ->selectRaw($this->monthExpression('expense_date') . ' as m, SUM(amount) as total')
            ->groupByRaw($this->monthExpression('expense_date'))
            ->pluck('total', 'm');

        for ($m = 1; $m <= 12; $m++) {
            $rev = (float) ($monthRevenueGroup[$m] ?? 0);
            $exp = (float) ($monthExpenseGroup[$m] ?? 0);
            $monthlyRevenueData[] = $rev;
            $monthlyExpenseData[] = $exp;
            $monthlyProfitData[] = $rev - $exp;
        }

        // 4. Cơ cấu chi phí theo danh mục (Pie/Donut Chart)
        $expenseCategories = Expense::categories();
        $categoryBreakdown = [];
        $categoryColors = [
            Expense::CATEGORY_ELECTRICITY => '#f59e0b',
            Expense::CATEGORY_WATER => '#06b6d4',
            Expense::CATEGORY_INTERNET => '#3b82f6',
            Expense::CATEGORY_MAINTENANCE => '#f43f5e',
            Expense::CATEGORY_CLEANING => '#14b8a6',
            Expense::CATEGORY_ASSET => '#8b5cf6',
            Expense::CATEGORY_OTHER => '#64748b',
        ];

        $categoryGroup = (clone $expenseQuery)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categoryChartLabels = [];
        $categoryChartValues = [];
        $categoryChartColors = [];

        foreach ($expenseCategories as $catKey => $catLabel) {
            $amount = (float) ($categoryGroup[$catKey] ?? 0);
            if ($amount > 0 || !$selectedMonth) {
                $categoryBreakdown[] = [
                    'key' => $catKey,
                    'label' => $catLabel,
                    'amount' => $amount,
                    'percent' => $totalExpenses > 0 ? round(($amount / $totalExpenses) * 100, 1) : 0,
                    'color' => $categoryColors[$catKey] ?? '#94a3b8',
                ];
                if ($amount > 0) {
                    $categoryChartLabels[] = $catLabel;
                    $categoryChartValues[] = $amount;
                    $categoryChartColors[] = $categoryColors[$catKey] ?? '#94a3b8';
                }
            }
        }

        // 5. Khoản chi lớn nhất gần đây
        $topExpenses = (clone $expenseQuery)
            ->with('room')
            ->orderByDesc('amount')
            ->take(6)
            ->get();

        // 6. Danh sách các năm khả dụng
        $years = Expense::query()->pluck('expense_date')
            ->map(fn ($d) => Carbon::parse($d)->year)
            ->merge(Payment::query()->pluck('payment_date')->map(fn ($d) => Carbon::parse($d)->year))
            ->push($currentYear)
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.overview.profit-loss', compact(
            'selectedYear',
            'selectedMonth',
            'selectedUtilityMonth',
            'selectedUtilityYear',
            'years',
            'totalRevenue',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'revenueDetails',
            'revenueDetailsCount',
            'expenseDetails',
            'expenseDetailsCount',
            'expenseCategoryLabels',
            'elecInvoiced',
            'totalElectricityUsage',
            'govElectricityUnitPrice',
            'suggestedGovElectricityUnitPrice',
            'elecGovEstimated',
            'elecPaidGov',
            'elecDiff',
            'elecGovBalance',
            'waterInvoiced',
            'totalWaterUsage',
            'govWaterUnitPrice',
            'suggestedGovWaterUnitPrice',
            'waterGovEstimated',
            'waterPaidGov',
            'waterDiff',
            'waterGovBalance',
            'monthsLabels',
            'monthlyRevenueData',
            'monthlyExpenseData',
            'monthlyProfitData',
            'categoryBreakdown',
            'categoryChartLabels',
            'categoryChartValues',
            'categoryChartColors',
            'topExpenses'
        ));
    }

    private function monthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%m', " . $column . ") AS INTEGER)",
            'pgsql' => "EXTRACT(MONTH FROM " . $column . ")",
            default => "MONTH(" . $column . ")",
        };
    }
}
