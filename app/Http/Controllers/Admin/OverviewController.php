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
    /**
     * Trang tá»•ng quan dashboard
     */
    public function index()
    {
        $currentYear  = now()->year;
        $previousYear = $currentYear - 1;

        // Tá»•ng doanh thu (tá»•ng tiá»n Ä‘Ã£ thu thÃ nh cÃ´ng)
        $totalRevenue = Payment::success()->sum('amount_paid');

        // Tá»•ng sá»‘ phÃ²ng
        $totalRooms = Room::count();

        // Há»£p Ä‘á»“ng Ä‘ang hoáº¡t Ä‘á»™ng
        $activeContracts = Contract::where('status', Contract::STATUS_ACTIVE)->count();

        // CÃ´ng ná»£: tá»•ng tiá»n chÆ°a thu cá»§a hÃ³a Ä‘Æ¡n chÆ°a / thanh toÃ¡n 1 pháº§n
        $outstandingIds  = Invoice::whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])->pluck('id');
        $totalBilledOut  = Invoice::whereIn('id', $outstandingIds)->sum('total_amount');
        $totalPaidOut    = Payment::success()->whereIn('invoice_id', $outstandingIds)->sum('amount_paid');
        $totalReceivable = max(0, $totalBilledOut - $totalPaidOut);

        // Doanh thu theo thÃ¡ng (closure ná»™i bá»™)
        $revenueByMonth = function (int $year): array {
            $rows = Payment::success()
                ->whereYear('payment_date', $year)
                ->selectRaw('MONTH(payment_date) as m, SUM(amount_paid) as total')
                ->groupBy('m')
                ->pluck('total', 'm');

            $result = [];
            for ($i = 1; $i <= 12; $i++) {
                $result[] = (float) ($rows[$i] ?? 0);
            }
            return $result;
        };

        $monthlyRevenueCurrentYear  = $revenueByMonth($currentYear);
        $monthlyRevenuePreviousYear = $revenueByMonth($previousYear);

        // Tráº¡ng thÃ¡i phÃ²ng
        $occupiedRooms    = Room::occupied()->count();
        $availableRooms   = Room::available()->count();
        $maintenanceRooms = Room::maintenance()->count();

        $occupiedPercent    = $totalRooms > 0 ? round(($occupiedRooms    / $totalRooms) * 100, 1) : 0;
        $availablePercent   = $totalRooms > 0 ? round(($availableRooms   / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        // Tráº¡ng thÃ¡i hÃ³a Ä‘Æ¡n
        $paidInvoices    = Invoice::where('status', Invoice::STATUS_PAID)->count();
        $unpaidInvoices  = Invoice::where('status', Invoice::STATUS_UNPAID)->count();
        $partialInvoices = Invoice::where('status', Invoice::STATUS_PARTIAL)->count();

        // Doanh thu hÃ´m nay & thÃ¡ng nÃ y
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

    /**
     * Biá»ƒu Ä‘á»“ doanh thu theo thÃ¡ng / nÄƒm
     */
    public function revenueChart()
    {
        $currentYear = now()->year;

        $rows = Payment::success()
            ->whereYear('payment_date', $currentYear)
            ->selectRaw('MONTH(payment_date) as m, SUM(amount_paid) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        $monthlyRevenue = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyRevenue[] = (float) ($rows[$i] ?? 0);
        }

        $yearRows = Payment::success()
            ->selectRaw('YEAR(payment_date) as y, SUM(amount_paid) as total')
            ->groupBy('y')
            ->orderBy('y')
            ->get();

        $yearLabels    = $yearRows->pluck('y')->map(fn($y) => (string) $y)->values()->toArray();
        $yearlyRevenue = $yearRows->pluck('total')->map(fn($v) => (float) $v)->values()->toArray();

        return view('admin.overview.revenue-chart', compact(
            'currentYear',
            'monthlyRevenue',
            'yearlyRevenue',
            'yearLabels'
        ));
    }

    /**
     * Thá»‘ng kÃª tá»•ng doanh thu
     */
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

    /**
     * Thá»‘ng kÃª sá»‘ phÃ²ng
     */
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

    /**
     * Tá»· lá»‡ láº¥p Ä‘áº§y phÃ²ng
     */
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