<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Csv;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Danh sách khách thuê
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['renting', 'not_renting'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $query = Tenant::with([
            'user',
            'contracts.room',
            'memberContracts.room',
            'vehicles',
        ]);

        $this->applyFilters($query, $search, $status);

        $tenants = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view(
                'admin.tenants.partials.results',
                compact('tenants')
            );
        }

        return view(
            'admin.tenants.index',
            compact('tenants', 'search', 'status')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Form xuất dữ liệu
    |--------------------------------------------------------------------------
    */

    public function exportForm()
    {
        $tenants = Tenant::with([
            'contracts.room',
            'vehicles',
        ])
            ->latest()
            ->paginate(10);

        return view(
            'admin.tenants.export',
            compact('tenants')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Export CSV
    |--------------------------------------------------------------------------
    */

    public function export(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['renting', 'not_renting'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $tenants = Tenant::with([
            'user',
            'contracts.room',
            'memberContracts.room',
            'vehicles',
        ]);

        $this->applyFilters($tenants, $search, $status);

        $tenants->orderBy('id');

        $filename =
            'danh_sach_khach_thue_'
            . now()->format('Ymd_His')
            . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' =>
            "attachment; filename=\"{$filename}\"",
        ];

        $columns = [
            'Họ tên',
            'CCCD',
            'SĐT',
            'Email',
            'Địa chỉ',
            'Phòng thuê',
            'Trạng thái',
            'Số xe',
            'Ngày tạo',
        ];

        $callback = function () use ($tenants, $columns) {
            $file = fopen('php://output', 'w');

            fprintf(
                $file,
                chr(0xEF)
                    . chr(0xBB)
                    . chr(0xBF)
            );

            Csv::writeRow($file, $columns);

            foreach ($tenants->lazy(500) as $tenant) {
                $activeRoom = $tenant->contracts
                    ->concat($tenant->memberContracts)
                    ->whereIn(
                        'status',
                        Contract::OPEN_OCCUPANCY_STATUSES
                    )
                    ->pluck('room.room_code')
                    ->first();

                Csv::writeRow($file, [
                    $tenant->full_name,
                    $tenant->cccd,
                    $tenant->phone,
                    $tenant->email,
                    $tenant->address,
                    $activeRoom ?? '-',
                    $activeRoom
                        ? 'Đang thuê'
                        : 'Chưa thuê',
                    $tenant->vehicles->count(),
                    $tenant->created_at->format('d/m/Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Form thêm khách thuê
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $users = User::whereHas(
            'role',
            fn($query) => $query->whereIn(
                'role_name',
                ['User', 'Client']
            )
        )
            ->whereIn(
                'status',
                [
                    User::STATUS_PENDING,
                    User::STATUS_ACTIVE,
                ]
            )
            ->doesntHave('tenant')
            ->get();

        return view(
            'admin.tenants.create',
            compact('users')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lưu khách thuê
    |--------------------------------------------------------------------------
    */

    public function store(TenantRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();

                /*
                |--------------------------------------------------------------------------
                | Tách danh sách xe khỏi dữ liệu Tenant
                |--------------------------------------------------------------------------
                */

                $vehicles = $data['vehicles'] ?? [];

                unset($data['vehicles']);

                /*
                |--------------------------------------------------------------------------
                | Tạo khách thuê
                |--------------------------------------------------------------------------
                */

                $tenant = Tenant::create($data);

                /*
                |--------------------------------------------------------------------------
                | Tạo xe
                |--------------------------------------------------------------------------
                */

                foreach ($vehicles as $vehicle) {
                    if (
                        empty($vehicle['license_plate'])
                        && empty($vehicle['vehicle_type'])
                        && empty($vehicle['vehicle_name'])
                    ) {
                        continue;
                    }

                    $tenant->vehicles()->create([
                        'vehicle_type' =>
                        $vehicle['vehicle_type'] ?? null,

                        'vehicle_name' =>
                        $vehicle['vehicle_name'] ?? null,

                        'license_plate' =>
                        $vehicle['license_plate'] ?? null,

                        'color' =>
                        $vehicle['color'] ?? null,

                        'note' =>
                        $vehicle['note'] ?? null,
                    ]);
                }
            });
        } catch (QueryException $exception) {
            $this->throwConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.tenants.index')
            ->with(
                'success',
                'Thêm khách thuê thành công.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Chi tiết khách thuê
    |--------------------------------------------------------------------------
    */

    public function show(Tenant $tenant)
    {
        $tenant->load([
            'user',
            'contracts.room',
            'memberContracts.room',
            'vehicles',
            'temporaryResidences.contract.room',
        ]);

        return view(
            'admin.tenants.show',
            compact('tenant')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Form sửa khách thuê
    |--------------------------------------------------------------------------
    */

    public function edit(Tenant $tenant)
    {
        $users = User::where(function ($query) {
            $query->whereHas(
                'role',
                fn($query) => $query->whereIn(
                    'role_name',
                    ['User', 'Client']
                )
            )
                ->whereIn(
                    'status',
                    [
                        User::STATUS_PENDING,
                        User::STATUS_ACTIVE,
                    ]
                )
                ->whereDoesntHave('tenant');
        })
            ->orWhere(
                'id',
                $tenant->user_id
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Load xe hiện tại
        |--------------------------------------------------------------------------
        */

        $tenant->load('vehicles');

        return view(
            'admin.tenants.edit',
            compact(
                'tenant',
                'users'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cập nhật khách thuê
    |--------------------------------------------------------------------------
    */

    public function update(
        TenantRequest $request,
        Tenant $tenant
    ) {
        try {
            DB::transaction(function () use (
                $request,
                $tenant
            ) {
                $data = $request->validated();

                $vehicles = $data['vehicles'] ?? [];

                unset($data['vehicles']);

                /*
                |--------------------------------------------------------------------------
                | Cập nhật thông tin Tenant
                |--------------------------------------------------------------------------
                */

                $tenant->update($data);

                /*
                |--------------------------------------------------------------------------
                | Xóa danh sách xe cũ
                |--------------------------------------------------------------------------
                |
                | Sau đó tạo lại theo dữ liệu form.
                |
                */

                $tenant->vehicles()->delete();

                foreach ($vehicles as $vehicle) {
                    if (
                        empty($vehicle['license_plate'])
                        && empty($vehicle['vehicle_type'])
                        && empty($vehicle['vehicle_name'])
                    ) {
                        continue;
                    }

                    $tenant->vehicles()->create([
                        'vehicle_type' =>
                        $vehicle['vehicle_type'] ?? null,

                        'vehicle_name' =>
                        $vehicle['vehicle_name'] ?? null,

                        'license_plate' =>
                        $vehicle['license_plate'] ?? null,

                        'color' =>
                        $vehicle['color'] ?? null,

                        'note' =>
                        $vehicle['note'] ?? null,
                    ]);
                }
            });
        } catch (QueryException $exception) {
            $this->throwConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.tenants.index')
            ->with(
                'success',
                'Cập nhật khách thuê thành công.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Xóa khách thuê
    |--------------------------------------------------------------------------
    */

    public function destroy(Tenant $tenant)
    {
        try {
            $deleted = DB::transaction(
                function () use ($tenant): bool {
                    $tenant = Tenant::lockForUpdate()
                        ->findOrFail($tenant->id);

                    if (
                        $tenant->contracts()->exists()
                        || $tenant->memberContracts()->exists()
                    ) {
                        return false;
                    }

                    $tenant->delete();

                    return true;
                }
            );
        } catch (QueryException $exception) {
            if (
                ! in_array(
                    (string) $exception->getCode(),
                    ['19', '23000'],
                    true
                )
            ) {
                throw $exception;
            }

            $deleted = false;
        }

        if (! $deleted) {
            return back()->with(
                'error',
                'Không thể xóa khách thuê vì khách đã có hợp đồng.'
            );
        }

        return back()->with(
            'success',
            'Xóa khách thuê thành công.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Xử lý lỗi trùng dữ liệu
    |--------------------------------------------------------------------------
    */

    private function throwConflictOrRethrow(
        QueryException $exception
    ): never {
        $message = strtolower(
            $exception->getMessage()
        );

        $fields = [
            'user_id' => [
                'tenants.user_id',
                'tenants_user_id_unique',
            ],

            'cccd' => [
                'tenants.cccd',
                'tenants_cccd_unique',
            ],

            'phone' => [
                'tenants.phone',
                'tenants_phone_unique',
            ],

            'email' => [
                'tenants.email',
                'tenants_email_unique',
            ],

            'license_plate' => [
                'vehicles.license_plate',
                'vehicles_license_plate_unique',
            ],
        ];

        foreach ($fields as $field => $needles) {
            if (
                collect($needles)->contains(
                    fn($needle) =>
                    str_contains(
                        $message,
                        strtolower($needle)
                    )
                )
            ) {
                $messageText =
                    $field === 'license_plate'
                    ? 'Biển số xe đã được sử dụng.'
                    : 'Dữ liệu đã được sử dụng bởi khách thuê khác.';

                throw ValidationException::withMessages([
                    $field => $messageText,
                ]);
            }
        }

        throw $exception;
    }

    /*
    |--------------------------------------------------------------------------
    | Bộ lọc khách thuê
    |--------------------------------------------------------------------------
    */

    private function applyFilters(
        $query,
        string $search,
        string $status
    ): void {
        $query->when(
            $search,
            function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'cccd',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'email',
                            'like',
                            "%{$search}%"
                        );
                });
            }
        );

        $occupying = fn($query) =>
        $query->whereIn(
            'contracts.status',
            Contract::OPEN_OCCUPANCY_STATUSES
        );

        if ($status === 'renting') {
            $query->where(function ($query) use ($occupying): void {
                $query->whereHas('contracts', $occupying)
                    ->orWhereHas('memberContracts', $occupying);
            });
        } elseif ($status === 'not_renting') {
            $query->whereDoesntHave(
                'contracts',
                $occupying
            )
                ->whereDoesntHave(
                    'memberContracts',
                    $occupying
                );
        }
    }
}
