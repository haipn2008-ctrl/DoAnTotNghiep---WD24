<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractTerminationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        // Hợp đồng đang hoạt động
        $activeContracts = Contract::where('status', Contract::STATUS_ACTIVE)->count();

        // Doanh thu tháng này
        $monthRevenue = Payment::success()
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid');

        // Tỷ lệ thu hồi và tổng tiền công nợ
        $totalBilledOut = $this->totalBilled();
        $totalRevenue = Payment::success()->sum('amount_paid');
        $totalReceivable = $this->totalReceivable();
        $collectionRate = $totalBilledOut > 0
            ? min(100, round(($totalRevenue / $totalBilledOut) * 100, 1))
            : 0;

        // Doanh thu theo tháng
        $monthlyRevenueCurrentYear = $this->monthlyRevenue($currentYear);
        $monthlyRevenuePreviousYear = $this->monthlyRevenue($previousYear);

        // Trạng thái phòng
        $totalRooms = Room::where('status', '!=', Room::STATUS_RETIRED)->count();
        $occupiedRooms = Room::occupied()->count();
        $availableRooms = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        // Doanh thu hôm nay
        $todayRevenue = Payment::success()
            ->whereDate('payment_date', today())
            ->sum('amount_paid');

        // --- Cảnh báo vận hành ---

        // Hợp đồng đang ở tháng cuối (từ hôm nay đến đúng một tháng tới).
        $expiringContracts = Contract::whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_EXPIRED])
            ->whereBetween('end_date', [today(), today()->addMonthNoOverflow()])
            ->with('room:id,room_code')
            ->orderBy('end_date')
            ->get(['id', 'contract_code', 'end_date', 'room_id']);

        // Hóa đơn quá hạn chưa thanh toán
        $overdueInvoices = Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->where('due_date', '<', today())
            ->selectRaw('COUNT(*) as count, SUM(total_amount + adjustment_amount) as total_amount')
            ->first();

        // Yêu cầu hỗ trợ chờ xử lý
        $pendingSupportCount = SupportRequest::where('status', SupportRequest::STATUS_NEW)->count();

        // Yêu cầu gia hạn và chấm dứt đang chờ duyệt
        $pendingExtensionCount = ContractExtensionRequest::where('status', ContractExtensionRequest::STATUS_PENDING)->count();
        $pendingTerminationCount = ContractTerminationRequest::where('status', ContractTerminationRequest::STATUS_PENDING)->count();

        return view('admin.overview.index', compact(
            'totalRooms',
            'activeContracts',
            'totalRevenue',
            'monthRevenue',
            'totalReceivable',
            'collectionRate',
            'monthlyRevenueCurrentYear',
            'monthlyRevenuePreviousYear',
            'currentYear',
            'previousYear',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'occupiedPercent',
            'availablePercent',
            'maintenancePercent',
            'todayRevenue',
            'expiringContracts',
            'overdueInvoices',
            'pendingSupportCount',
            'pendingExtensionCount',
            'pendingTerminationCount',
        ));
    }

    public function revenueChart(Request $request)
    {
        $defaultDate = now()->copy()->subMonthNoOverflow();
        $filters = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $reportYear = (int) ($filters['year'] ?? $defaultDate->year);
        $reportMonth = (int) ($filters['month'] ?? $defaultDate->month);
        $reportDate = now()->copy()->startOfMonth()->setYear($reportYear)->setMonth($reportMonth);
        $currentYear = $reportDate->year;

        // Last month invoice breakdown
        $monthSummary = Invoice::whereYear('invoice_date', $reportYear)
            ->whereMonth('invoice_date', $reportMonth)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->selectRaw('
                COALESCE(SUM(room_fee),0)        as room_fee,
                COALESCE(SUM(electricity_fee),0) as electricity_fee,
                COALESCE(SUM(water_fee),0)       as water_fee,
                COALESCE(SUM(internet_fee),0)    as internet_fee,
                COALESCE(SUM(service_fee),0)     as service_fee,
                COALESCE(SUM(total_amount + adjustment_amount),0) as total_invoiced
            ')
            ->first();

        $fixedRevenue = (float) ($monthSummary->room_fee ?? 0);
        $totalInvoiced = (float) ($monthSummary->total_invoiced ?? 0);
        // Đã thu thực tế: chỉ tính payments thuộc hóa đơn tháng trước
        $actualRevenue = (float) Payment::success()
            ->whereHas('invoice', fn ($q) => $q
                ->whereYear('invoice_date', $reportYear)
                ->whereMonth('invoice_date', $reportMonth)
            )
            ->sum('amount_paid');

        $totalBilled = $this->totalBilled();
        $totalRevenue = (float) Payment::success()->sum('amount_paid');

        $totalReceivable = $this->totalReceivable();
        $monthlyReceivable = (float) Invoice::query()
            ->whereYear('invoice_date', $reportYear)
            ->whereMonth('invoice_date', $reportMonth)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->get()
            ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount);

        $totalRooms = Room::where('status', '!=', Room::STATUS_RETIRED)->count();
        $occupiedRooms = Room::occupied()->count();
        $fillRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Phân tích theo danh mục — 12 tháng trong năm hiện tại
        $categoryLabels = [];
        $catRoom = $catElec = $catWater = $catInternet = $catService = [];

        for ($m = 1; $m <= 12; $m++) {
            $row = Invoice::whereYear('invoice_date', $currentYear)
                ->whereMonth('invoice_date', $m)
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->selectRaw('
                    COALESCE(SUM(room_fee),0)        as room,
                    COALESCE(SUM(electricity_fee),0) as elec,
                    COALESCE(SUM(water_fee),0)       as water,
                    COALESCE(SUM(internet_fee),0)    as internet,
                    COALESCE(SUM(service_fee),0)     as service
                ')
                ->first();

            $categoryLabels[] = 'T'.$m;
            $catRoom[] = (float) $row->room;
            $catElec[] = (float) $row->elec;
            $catWater[] = (float) $row->water;
            $catInternet[] = (float) $row->internet;
            $catService[] = (float) $row->service;
        }

        $breakdownItems = [
            ['label' => 'Tiền phòng',  'value' => (float) ($monthSummary->room_fee ?? 0), 'color' => 'bg-indigo-500'],
            ['label' => 'Tiền điện',   'value' => (float) ($monthSummary->electricity_fee ?? 0), 'color' => 'bg-amber-400'],
            ['label' => 'Tiền nước',   'value' => (float) ($monthSummary->water_fee ?? 0), 'color' => 'bg-cyan-400'],
            ['label' => 'Internet',    'value' => (float) ($monthSummary->internet_fee ?? 0), 'color' => 'bg-emerald-500'],
            ['label' => 'Dịch vụ',     'value' => (float) ($monthSummary->service_fee ?? 0), 'color' => 'bg-violet-500'],
        ];
        $remaining = $monthlyReceivable;

        $years = Invoice::query()->pluck('invoice_date')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->year)
            ->merge(Payment::query()->pluck('payment_date')->map(fn ($d) => \Carbon\Carbon::parse($d)->year))
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('admin.overview.revenue-chart', compact(
            'currentYear', 'reportYear', 'reportMonth',
            'fixedRevenue', 'actualRevenue', 'totalInvoiced', 'remaining',
            'totalReceivable', 'monthlyReceivable', 'fillRate', 'totalRooms', 'occupiedRooms',
            'monthSummary', 'breakdownItems',
            'categoryLabels', 'catRoom', 'catElec', 'catWater', 'catInternet', 'catService',
            'years'
        ));
    }

    public function revenueStats()
    {
        $currentYear = now()->year;

        $totalRevenue = Payment::success()->sum('amount_paid');
        $totalBilled = $this->totalBilled();
        $totalReceivable = $this->totalReceivable();

        $collectionRate = $totalBilled > 0
            ? min(100, round(($totalRevenue / $totalBilled) * 100, 1))
            : 0;

        $todayRevenue = Payment::success()
            ->whereDate('payment_date', today())
            ->sum('amount_paid');

        $monthRevenue = Payment::success()
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid');

        return view('admin.overview.revenue-stats', compact(
            'totalRevenue',
            'totalBilled',
            'totalReceivable',
            'collectionRate',
            'todayRevenue',
            'monthRevenue'
        ));
    }

    private function totalBilled(): float
    {
        return (float) Invoice::query()
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->sum(DB::raw('total_amount + adjustment_amount'));
    }

    private function totalReceivable(): float
    {
        return (float) Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->get()
            ->sum(fn (Invoice $invoice) => (float) $invoice->remaining_amount);
    }

    public function roomStats()
    {
        $totalRooms = Room::where('status', '!=', Room::STATUS_RETIRED)->count();
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

    public function fillRate()
    {
        $totalRooms = Room::where('status', '!=', Room::STATUS_RETIRED)->count();
        $occupiedRooms = Room::occupied()->count();
        $availableRooms = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        return view('admin.overview.fill-rate', compact(
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'maintenanceRooms',
            'occupiedPercent',
            'availablePercent',
            'maintenancePercent'
        ));
    }

    private function monthlyRevenue(int $year): array
    {
        $grouped = Payment::success()
            ->whereYear('payment_date', $year)
            ->selectRaw($this->monthExpression().' as revenue_month, SUM(amount_paid) as total')
            ->groupByRaw($this->monthExpression())
            ->pluck('total', 'revenue_month');

        return collect(range(1, 12))
            ->map(fn (int $month) => (float) ($grouped[$month] ?? 0))
            ->all();
    }

    private function monthExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%m', payment_date) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM payment_date)',
            default => 'MONTH(payment_date)',
        };
    }

    private function yearExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y', payment_date)",
            'pgsql' => 'EXTRACT(YEAR FROM payment_date)',
            default => 'YEAR(payment_date)',
        };
    }
}
