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
        $currentYear  = now()->year;
        $previousYear = $currentYear - 1;

        // Tổng doanh thu (tổng tiền đã thu thành công)
        $totalRevenue = Payment::success()->sum('amount_paid');

        // Hợp đồng đang hoạt động
        $activeContracts = Contract::where('status', Contract::STATUS_ACTIVE)->count();

        // Công nợ: tổng tiền chưa thu của hóa đơn chưa / thanh toán 1 phần
        $outstandingIds  = Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])->pluck('id');
        $totalBilledOut  = Invoice::whereIn('id', $outstandingIds)->sum('total_amount');
        $totalPaidOut    = Payment::success()->whereIn('invoice_id', $outstandingIds)->sum('amount_paid');
        $totalReceivable = max(0, $totalBilledOut - $totalPaidOut);

        // Doanh thu theo tháng
        $revenueByMonth = function (int $year): array {
            $payments = Payment::success()
                ->whereYear('payment_date', $year)
                ->get(['payment_date', 'amount_paid']);

            $grouped = $payments
                ->groupBy(fn($p) => (int) $p->payment_date->format('n'))
                ->map(fn($g) => $g->sum('amount_paid'));

            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = (float) ($grouped[$i] ?? 0);
            }
            return $result;
        };

        $monthlyRevenueCurrentYear  = $revenueByMonth($currentYear);
        $monthlyRevenuePreviousYear = $revenueByMonth($previousYear);

        // Tổng số phòng
        $totalRooms = Room::count();

        // Trạng thái phòng
        $occupiedRooms    = Room::occupied()->count();
        $availableRooms   = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent    = $totalRooms > 0 ? round(($occupiedRooms    / $totalRooms) * 100, 1) : 0;
        $availablePercent   = $totalRooms > 0 ? round(($availableRooms   / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        // Trạng thái hóa đơn
        $paidInvoices    = Invoice::where('status', Invoice::STATUS_PAID)->count();
        $unpaidInvoices  = Invoice::where('status', Invoice::STATUS_UNPAID)->count();
        $partialInvoices = Invoice::where('status', Invoice::STATUS_PARTIAL)->count();

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
            'todayRevenue',
            'monthRevenue'
        ));
    }

    public function revenueChart()
    {
        $currentYear = now()->year;

        $payments = Payment::success()
            ->whereYear('payment_date', $currentYear)
            ->get(['payment_date', 'amount_paid']);

        $grouped = $payments
            ->groupBy(fn($p) => (int) $p->payment_date->format('n'))
            ->map(fn($g) => $g->sum('amount_paid'));

        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = (float) ($grouped[$i] ?? 0);
        }

        $allPayments = Payment::success()
            ->whereNotNull('payment_date')
            ->get(['payment_date', 'amount_paid']);

        $yearlyGrouped = $allPayments
            ->groupBy(fn($p) => $p->payment_date->format('Y'))
            ->map(fn($g) => $g->sum('amount_paid'))
            ->sortKeys();

        $yearLabels    = $yearlyGrouped->keys()->values()->toArray();
        $yearlyRevenue = $yearlyGrouped->values()->map(fn($v) => (float) $v)->toArray();

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

        $totalRevenue    = Payment::success()->sum('amount_paid');
        $totalBilled     = Invoice::sum('total_amount');
        $totalReceivable = max(0, $totalBilled - $totalRevenue);

        $collectionRate = $totalBilled > 0
            ? round(($totalRevenue / $totalBilled) * 100, 1)
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
        $totalRooms       = Room::count();
        $occupiedRooms    = Room::occupied()->count();
        $availableRooms   = Room::available()->count();
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
        $totalRooms       = Room::count();
        $occupiedRooms    = Room::occupied()->count();
        $availableRooms   = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent    = $totalRooms > 0 ? round(($occupiedRooms    / $totalRooms) * 100, 1) : 0;
        $availablePercent   = $totalRooms > 0 ? round(($availableRooms   / $totalRooms) * 100, 1) : 0;
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
}
