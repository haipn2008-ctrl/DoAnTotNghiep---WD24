<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Contract;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AdminNotificationService;
use App\Services\VehicleCapacityService;
use App\Support\Csv;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'vehicles.tenant',
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
        $tenant = null;

        try {
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

            DB::transaction(function () use ($tenant, $vehicles): void {
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
            if ($tenant?->exists) {
                $tenant->delete();
            }

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
            'vehicles.tenant',
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
        /*
        |--------------------------------------------------------------------------
        | Load xe hiện tại
        |--------------------------------------------------------------------------
        */

        $tenant->load(['user', 'vehicles']);

        return view(
            'admin.tenants.edit',
            compact('tenant')
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

                /*
                |--------------------------------------------------------------------------
                | Cập nhật thông tin Tenant
                |--------------------------------------------------------------------------
                */

                $tenant->update($data);

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

    public function reviewVehicle(
        Request $request,
        Vehicle $vehicle,
        AdminNotificationService $notifications,
        VehicleCapacityService $capacity,
    )
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Vehicle::STATUS_APPROVED, Vehicle::STATUS_REJECTED])],
            'review_note' => ['nullable', 'required_if:status,'.Vehicle::STATUS_REJECTED, 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($vehicle, $data, $request, $capacity): void {
            $vehicle = Vehicle::query()->with('tenant')->lockForUpdate()->findOrFail($vehicle->id);
            if ($data['status'] === Vehicle::STATUS_APPROVED) {
                $capacity->ensureCanApprove($vehicle);
            }
            $vehicle->update([
                'status' => $data['status'],
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }, 3);
        $vehicle->refresh();
        $notifications->vehicleReviewed($vehicle);

        return back()->with('success', $data['status'] === Vehicle::STATUS_APPROVED
            ? 'Đã duyệt phương tiện.'
            : 'Đã từ chối phương tiện.');
    }

    public function vehicleImage(Vehicle $vehicle): StreamedResponse
    {
        abort_unless(
            filled($vehicle->vehicle_image)
            && str_starts_with($vehicle->vehicle_image, 'vehicles/')
            && Storage::disk('local')->exists($vehicle->vehicle_image),
            404
        );

        return Storage::disk('local')->response($vehicle->vehicle_image, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
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
