<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
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

        // Tổng doanh thu
        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');

        // Doanh thu theo tháng (2025 & 2026)
        $monthlyRevenue2025 = [];
        $monthlyRevenue2026 = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenue2025[] = Invoice::where('status', 'paid')
                ->whereYear('invoice_date', 2025)
                ->whereMonth('invoice_date', $month)
                ->sum('total_amount') ?? 0;

            $monthlyRevenue2026[] = Invoice::where('status', 'paid')
                ->whereYear('invoice_date', 2026)
                ->whereMonth('invoice_date', $month)
                ->sum('total_amount') ?? 0;
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

        // Doanh thu hôm nay
        $todayRevenue = Invoice::where('status', 'paid')
            ->whereDate('invoice_date', date('Y-m-d'))
            ->sum('total_amount') ?? 0;

        // Doanh thu tháng này
        $monthRevenue = Invoice::where('status', 'paid')
            ->whereYear('invoice_date', date('Y'))
            ->whereMonth('invoice_date', date('m'))
            ->sum('total_amount') ?? 0;

        // Contracts hoạt động
        $activeContracts = Contract::where('status', 'active')->count();

        return view('admin.overview.index', [
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue2025' => $monthlyRevenue2025,
            'monthlyRevenue2026' => $monthlyRevenue2026,

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

        $monthlyRevenue = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyRevenue[] = Invoice::where('status', 'paid')
                ->whereYear('invoice_date', date('Y'))
                ->whereMonth('invoice_date', $month)
                ->sum('total_amount') ?? 0;
        }

        return view('admin.overview.revenue-chart', compact('monthlyRevenue'));
    }

    public function revenueStats()
    {
        $user = auth()->user();

        if ($user->role_id !== 1) {
            return redirect()->route('dashboard');
        }

        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $todayRevenue = Invoice::where('status', 'paid')
            ->whereDate('invoice_date', date('Y-m-d'))
            ->sum('total_amount') ?? 0;

        $monthRevenue = Invoice::where('status', 'paid')
            ->whereYear('invoice_date', date('Y'))
            ->whereMonth('invoice_date', date('m'))
            ->sum('total_amount') ?? 0;

        return view('admin.overview.revenue-stats', compact('totalRevenue', 'todayRevenue', 'monthRevenue'));
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
