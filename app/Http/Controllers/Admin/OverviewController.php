<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function index()
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        // Tổng doanh thu (tổng tiền đã thu thành công)
        $totalRevenue = Payment::success()->sum('amount_paid');

        // Hợp đồng đang hoạt động
        $activeContracts = Contract::where('status', Contract::STATUS_ACTIVE)->count();

        // Công nợ thực tế: tổng tiền hóa đơn trừ tổng thanh toán thành công
        $totalBilledOut = Invoice::sum('total_amount');
        $totalReceivable = max(0, $totalBilledOut - $totalRevenue);

        // Doanh thu theo tháng
        $monthlyRevenueCurrentYear = $this->monthlyRevenue($currentYear);
        $monthlyRevenuePreviousYear = $this->monthlyRevenue($previousYear);

        // Tổng số phòng
        $totalRooms = Room::count();

        // Trạng thái phòng
        $occupiedRooms = Room::occupied()->count();
        $availableRooms = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        // Trạng thái hóa đơn
        $paidInvoices = Invoice::where('status', Invoice::STATUS_PAID)->count();
        $unpaidInvoices = Invoice::where('status', Invoice::STATUS_UNPAID)->count();
        $partialInvoices = Invoice::where('status', Invoice::STATUS_PARTIAL)->count();
        $outstandingInvoices = $unpaidInvoices + $partialInvoices;

        // Doanh thu hôm nay & tháng này
        $todayRevenue = Payment::success()
            ->whereDate('payment_date', today())
            ->sum('amount_paid');

        $monthRevenue = Payment::success()
            ->whereYear('payment_date', $currentYear)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid');

        return view('admin.overview.index', compact(
            'totalRevenue',
            'totalRooms',
            'activeContracts',
            'totalReceivable',
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
            'paidInvoices',
            'unpaidInvoices',
            'partialInvoices',
            'outstandingInvoices',
            'todayRevenue',
            'monthRevenue'
        ));
    }

    public function revenueChart()
    {
        $currentYear = now()->year;

        $monthlyRevenue = $this->monthlyRevenue($currentYear);
        $yearlyGrouped = Payment::success()
            ->whereNotNull('payment_date')
            ->selectRaw($this->yearExpression().' as revenue_year, SUM(amount_paid) as total')
            ->groupByRaw($this->yearExpression())
            ->orderBy('revenue_year')
            ->get();

        $yearLabels = $yearlyGrouped->pluck('revenue_year')->map(fn ($year) => (string) $year)->all();
        $yearlyRevenue = $yearlyGrouped->pluck('total')->map(fn ($total) => (float) $total)->all();

        return view('admin.overview.revenue-chart', compact(
            'currentYear',
            'monthlyRevenue',
            'yearlyRevenue',
            'yearLabels'
        ));
    }

    public function revenueStats()
    {
        $currentYear = now()->year;

        $totalRevenue = Payment::success()->sum('amount_paid');
        $totalBilled = Invoice::sum('total_amount');
        $totalReceivable = max(0, $totalBilled - $totalRevenue);

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

    public function roomStats()
    {
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

    public function fillRate()
    {
        $totalRooms = Room::count();
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
