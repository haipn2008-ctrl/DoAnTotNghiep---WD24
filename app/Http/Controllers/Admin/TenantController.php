<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Csv;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        $tenants = Tenant::with([
            'user',
            'contracts.room',
            'memberContracts.room',
        ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('cccd', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view(
            'admin.tenants.index',
            compact('tenants', 'search')
        );
    }

    public function exportForm()
    {
        $tenants = Tenant::with(['contracts.room', 'memberContracts.room'])
            ->latest()
            ->paginate(10);

        return view('admin.tenants.export', compact('tenants'));
    }

    public function export()
    {
        $tenants = Tenant::with([
            'user',
            'contracts.room',
            'memberContracts.room',
        ])
            ->orderBy('id');

        $filename = 'danh_sach_khach_thue_'.now()->format('Ymd_His').'.csv';

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
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            Csv::writeRow($file, $columns);

            foreach ($tenants->lazy(500) as $tenant) {
                $activeRoom = $tenant->contracts->concat($tenant->memberContracts)
                    ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
                    ->pluck('room.room_code')
                    ->first();

                Csv::writeRow($file, [
                    $tenant->full_name,
                    $tenant->cccd,
                    $tenant->phone,
                    $tenant->email,
                    $tenant->address,
                    $activeRoom ?? '-',
                    $activeRoom ? 'Đang thuê' : 'Chưa thuê',
                    $tenant->created_at->format('d/m/Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $users = User::whereHas('role', fn ($query) => $query->whereIn('role_name', ['User', 'Client']))
            ->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE])
            ->doesntHave('tenant')
            ->get();

        return view(
            'admin.tenants.create',
            compact('users')
        );
    }

    public function store(TenantRequest $request)
    {
        try {
            Tenant::create($request->validated());
        } catch (QueryException $exception) {
            $this->throwConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Thêm khách thuê thành công');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['user', 'contracts.room', 'memberContracts.room']);

        return view(
            'admin.tenants.show',
            compact('tenant')
        );
    }

    public function edit(Tenant $tenant)
    {
        $users = User::where(function ($query) {
            $query->whereHas('role', fn ($query) => $query->whereIn('role_name', ['User', 'Client']))
                ->whereIn('status', [User::STATUS_PENDING, User::STATUS_ACTIVE])
                ->whereDoesntHave('tenant');
        })
            ->orWhere('id', $tenant->user_id)
            ->get();

        return view(
            'admin.tenants.edit',
            compact('tenant', 'users')
        );
    }

    public function update(TenantRequest $request, Tenant $tenant)
    {
        try {
            $tenant->update($request->validated());
        } catch (QueryException $exception) {
            $this->throwConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Cập nhật khách thuê thành công');
    }

    public function destroy(Tenant $tenant)
    {
        try {
            $deleted = DB::transaction(function () use ($tenant): bool {
                $tenant = Tenant::lockForUpdate()->findOrFail($tenant->id);

                if ($tenant->contracts()->exists() || $tenant->memberContracts()->exists()) {
                    return false;
                }

                $tenant->delete();

                return true;
            });
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['19', '23000'], true)) {
                throw $exception;
            }

            $deleted = false;
        }

        if (! $deleted) {
            return back()
                ->with('error', 'Khách thuê đã có hợp đồng');
        }

        return back()
            ->with('success', 'Xóa khách thuê thành công');
    }

    private function throwConflictOrRethrow(QueryException $exception): never
    {
        $message = strtolower($exception->getMessage());
        $fields = [
            'user_id' => ['tenants.user_id', 'tenants_user_id_unique'],
            'cccd' => ['tenants.cccd', 'tenants_cccd_unique'],
            'phone' => ['tenants.phone', 'tenants_phone_unique'],
            'email' => ['tenants.email', 'tenants_email_unique'],
        ];

        foreach ($fields as $field => $needles) {
            if (collect($needles)->contains(fn ($needle) => str_contains($message, $needle))) {
                throw ValidationException::withMessages([
                    $field => 'Dữ liệu đã được sử dụng bởi khách thuê khác.',
                ]);
            }
        }

        throw $exception;
    }
}
