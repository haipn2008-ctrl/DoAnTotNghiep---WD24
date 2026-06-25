<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenants = Tenant::with([
            'contracts.room'
        ])
            ->latest()
            ->paginate(10);

        return view(
            'admin.tenants.index',
            compact('tenants')
        );
    }

    public function exportForm()
    {
        $tenants = Tenant::with(['contracts.room'])
            ->latest()
            ->paginate(10);

        return view('admin.tenants.export', compact('tenants'));
    }

    public function export()
    {
        $tenants = Tenant::with(['contracts.room'])
            ->latest()
            ->get();

        $filename = 'danh_sach_khach_thue_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Họ tên',
            'CCCD',
            'SĐT',
            'Email',
            'Địa chỉ',
            'Phòng thuê',
            'Trạng thái',
            'Ngày tạo',
        ];

        $callback = function () use ($tenants, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($tenants as $tenant) {
                $activeRoom = $tenant->contracts
                    ->where('status', 'active')
                    ->pluck('room.room_code')
                    ->first();

                $status = $activeRoom ? 'Đang thuê' : 'Chưa thuê';

                fputcsv($file, [
                    $tenant->full_name,
                    $tenant->cccd,
                    $tenant->phone,
                    $tenant->email,
                    $tenant->address,
                    $activeRoom ?? '-',
                    $status,
                    $tenant->created_at->format('d/m/Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.tenants.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TenantRequest $request)
    {
        Tenant::create($request->validated());

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Thêm khách thuê thành công');
    }

    public function show(Tenant $tenant)
    {
        return view(
            'admin.tenants.show',
            compact('tenant')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        return view(
            'admin.tenants.edit',
            compact('tenant')
        );
    }

    public function update(
        TenantRequest $request,
        Tenant $tenant
    ) {

        $tenant->update(
            $request->validated()
        );

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Cập nhật thành công');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        if ($tenant->contracts()->exists()) {

            return back()
                ->with(
                    'error',
                    'Khách thuê đã có hợp đồng'
                );
        }

        $tenant->delete();

        return back()
            ->with(
                'success',
                'Xóa thành công'
            );
    }
}
