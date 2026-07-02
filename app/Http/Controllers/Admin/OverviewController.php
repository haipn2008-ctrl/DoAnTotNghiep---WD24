<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Chỉ cho phép admin
        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        // Tổng doanh thu thực nhận từ thanh toán thành công
        $totalRevenue = Payment::where('status', 'success')->sum('amount_paid');

        // Doanh thu theo tháng cho năm hiện tại và năm trước đó từ các khoản thanh toán thực tế
        $monthlyRevenuePreviousYear = [];
        $monthlyRevenueCurrentYear = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenuePreviousYear[] = Payment::where('status', 'success')
                ->whereYear('payment_date', $previousYear)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');

            $monthlyRevenueCurrentYear[] = Payment::where('status', 'success')
                ->whereYear('payment_date', $currentYear)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');
        }


        // Tổng phòng
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        // Tỷ lệ phòng (%)
        $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        // Hóa đơn chưa thanh toán
        $unpaidInvoices = Invoice::where('status', 'unpaid')->count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $partialInvoices = Invoice::where('status', 'partial')->count();

        // Doanh thu hôm nay và tháng này từ thanh toán thực tế
        $todayRevenue = Payment::where('status', 'success')
            ->whereDate('payment_date', now()->toDateString())
            ->sum('amount_paid') ?? 0;

        $monthRevenue = Payment::where('status', 'success')
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount_paid') ?? 0;

        $totalBilled = Invoice::sum('total_amount');
        $totalReceivable = Invoice::get()->sum(function ($invoice) {
            return $invoice->balance_amount;
        });

        $collectionRate = $totalBilled > 0
            ? round(($totalRevenue / $totalBilled) * 100, 1)
            : 0;

        // Contracts hoạt động
        $activeContracts = Contract::where('status', 'active')->count();

        return view('admin.overview.index', [
            'totalRevenue' => $totalRevenue,
            'totalBilled' => $totalBilled,
            'totalReceivable' => $totalReceivable,
            'collectionRate' => $collectionRate,
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'monthlyRevenuePreviousYear' => $monthlyRevenuePreviousYear,
            'monthlyRevenueCurrentYear' => $monthlyRevenueCurrentYear,

            'totalRooms' => $totalRooms,
            'occupiedRooms' => $occupiedRooms,
            'availableRooms' => $availableRooms,
            'maintenanceRooms' => $maintenanceRooms,
            'occupiedPercent' => $occupiedPercent,
            'availablePercent' => $availablePercent,
            'maintenancePercent' => $maintenancePercent,
            'unpaidInvoices' => $unpaidInvoices,
            'paidInvoices' => $paidInvoices,
            'partialInvoices' => $partialInvoices,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
            'activeContracts' => $activeContracts,
        ]);
    }

    public function revenueChart()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $currentYear = date('Y');
        $monthlyRevenue = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenue[] = (float) Payment::where('status', 'success')
                ->whereYear('payment_date', $currentYear)
                ->whereMonth('payment_date', $month)
                ->sum('amount_paid');
        }

        $yearLabels = [];
        $yearlyRevenue = [];
        for ($year = $currentYear - 4; $year <= $currentYear; $year++) {
            $yearLabels[] = $year;
            $yearlyRevenue[] = (float) Payment::where('status', 'success')
                ->whereYear('payment_date', $year)
                ->sum('amount_paid');
        }

        return view('admin.overview.revenue-chart', compact('monthlyRevenue', 'yearLabels', 'yearlyRevenue', 'currentYear'));
    }

    public function revenueStats()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRevenue = Payment::where('status', 'success')->sum('amount_paid');
        $todayRevenue = Payment::where('status', 'success')
            ->whereDate('payment_date', date('Y-m-d'))
            ->sum('amount_paid') ?? 0;

        $monthRevenue = Payment::where('status', 'success')
            ->whereYear('payment_date', date('Y'))
            ->whereMonth('payment_date', date('m'))
            ->sum('amount_paid') ?? 0;

        $totalBilled = Invoice::sum('total_amount');
        $totalReceivable = Invoice::get()->sum(function ($invoice) {
            return $invoice->balance_amount;
        });
        $collectionRate = $totalBilled > 0
            ? round(($totalRevenue / $totalBilled) * 100, 1)
            : 0;

        return view('admin.overview.revenue-stats', compact('totalRevenue', 'todayRevenue', 'monthRevenue', 'totalBilled', 'totalReceivable', 'collectionRate'));
    }

    public function roomStats()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        return view('admin.overview.room-stats', compact('totalRooms', 'occupiedRooms', 'availableRooms', 'maintenanceRooms'));
    }

    public function fillRate()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();
        $maintenanceRooms = Room::where('status', 'maintenance')->count();

        $occupiedPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $availablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100, 1) : 0;
        $maintenancePercent = $totalRooms > 0 ? round(($maintenanceRooms / $totalRooms) * 100, 1) : 0;

        return view('admin.overview.fill-rate', compact('occupiedPercent', 'availablePercent', 'maintenancePercent', 'totalRooms', 'occupiedRooms', 'availableRooms', 'maintenanceRooms'));
    }
}
