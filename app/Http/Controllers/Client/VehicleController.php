<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ContractTenant;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Services\AdminNotificationService;
use App\Services\VehicleCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class VehicleController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notifications,
        private readonly VehicleCapacityService $capacity,
    ) {}

    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant()->firstOrFail();
        $contract = $this->capacity->currentContract($tenant);
        $owners = $contract && $contract->isManagedBy($request->user())
            ? $contract->members()->with('tenant.vehicles.reviewer')
                ->where('status', ContractTenant::STATUS_CHECKED_IN)
                ->whereNotNull('tenant_id')->get()->pluck('tenant')->filter()->unique('id')->values()
            : collect([$tenant->load('vehicles.reviewer')]);
        $vehicles = $owners->flatMap(fn (Tenant $owner) => $owner->vehicles)
            ->whereNotIn('status', [Vehicle::STATUS_CANCELLED, Vehicle::STATUS_REMOVED])
            ->sortByDesc('created_at')->values();

        return view('client.vehicles.index', compact('tenant', 'contract', 'owners', 'vehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant()->firstOrFail();
        $owner = $this->vehicleOwner($request, $tenant);
        $data = $this->validatedVehicle($request);
        $imagePath = $request->file('vehicle_image')?->store('vehicles', 'local');

        try {
            $vehicle = DB::transaction(function () use ($owner, $data, $imagePath, $request): Vehicle {
                $this->capacity->ensureCanSubmit($owner);

                return $owner->vehicles()->create($data + [
                    'vehicle_image' => $imagePath,
                    'status' => Vehicle::STATUS_PENDING,
                    'submitted_by' => $request->user()->id,
                ]);
            }, 3);
            $this->notifications->vehicleSubmitted($vehicle);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('local')->delete($imagePath);
            }
            throw $exception;
        }

        return back()->with('success', 'Đã gửi phương tiện để quản trị viên duyệt.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle = $this->managedVehicle($request, $vehicle);
        abort_if(in_array($vehicle->status, [Vehicle::STATUS_CANCELLED, Vehicle::STATUS_REMOVED], true), 409, 'Phương tiện đã được lưu vào lịch sử và không thể cập nhật.');
        abort_if($vehicle->status === Vehicle::STATUS_PENDING, 409, 'Yêu cầu đang chờ duyệt. Hãy hủy yêu cầu nếu cần đăng ký lại.');
        $data = $this->validatedVehicle($request, $vehicle);
        $oldImagePath = $vehicle->vehicle_image;
        $newImagePath = $request->file('vehicle_image')?->store('vehicles', 'local');

        try {
            DB::transaction(function () use ($vehicle, $data, $newImagePath, $oldImagePath, $request): void {
                $vehicle = Vehicle::query()->with('tenant')->lockForUpdate()->findOrFail($vehicle->id);
                $this->capacity->ensureCanSubmit($vehicle->tenant, $vehicle);
                $vehicle->update($data + [
                    'vehicle_image' => $newImagePath ?: $oldImagePath,
                    'status' => Vehicle::STATUS_PENDING,
                    'submitted_by' => $request->user()->id,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_note' => null,
                ]);
            }, 3);
            $this->notifications->vehicleSubmitted($vehicle, true);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('local')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath && $oldImagePath) {
            Storage::disk('local')->delete($oldImagePath);
        }

        return back()->with('success', 'Đã cập nhật và gửi lại phương tiện để duyệt.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle = $this->managedVehicle($request, $vehicle);
        abort_if(in_array($vehicle->status, [Vehicle::STATUS_CANCELLED, Vehicle::STATUS_REMOVED], true), 409, 'Phương tiện đã được gỡ hoặc hủy trước đó.');
        $data = $request->validate([
            'removal_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $wasApproved = $vehicle->status === Vehicle::STATUS_APPROVED;

        DB::transaction(function () use ($vehicle, $request, $data, $wasApproved): void {
            $lockedVehicle = Vehicle::query()->with('tenant')->lockForUpdate()->findOrFail($vehicle->id);

            if ($wasApproved) {
                $this->notifications->vehicleRemoved($lockedVehicle);
            } else {
                $this->notifications->vehicleRequestCancelled($lockedVehicle);
            }

            $lockedVehicle->forceFill([
                'archived_license_plate' => $lockedVehicle->license_plate,
                'license_plate' => null,
                'status' => $wasApproved ? Vehicle::STATUS_REMOVED : Vehicle::STATUS_CANCELLED,
                'removed_at' => now(),
                'removed_by' => $request->user()->id,
                'removal_reason' => $data['removal_reason'],
            ])->save();
        }, 3);

        return back()->with('success', $wasApproved
            ? 'Đã gỡ phương tiện và giữ lại lịch sử xét duyệt.'
            : 'Đã hủy yêu cầu và giữ lại lịch sử. Bạn có thể đăng ký lại phương tiện từ đầu.');
    }

    public function image(Request $request, Vehicle $vehicle): StreamedResponse
    {
        $vehicle = $this->managedVehicle($request, $vehicle);
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

    private function managedVehicle(Request $request, Vehicle $vehicle): Vehicle
    {
        $tenant = $request->user()->tenant;
        $contract = $tenant ? $this->capacity->currentContract($tenant) : null;
        abort_unless($contract, 409, 'Không tìm thấy hợp đồng đang ở để quản lý phương tiện.');
        $ownerIds = $contract && $contract->isManagedBy($request->user())
            ? $contract->members()->where('status', ContractTenant::STATUS_CHECKED_IN)->pluck('tenant_id')
            : collect([$tenant?->id]);
        abort_unless($ownerIds->contains($vehicle->tenant_id), 404);

        return $vehicle;
    }

    private function vehicleOwner(Request $request, Tenant $tenant): Tenant
    {
        $contract = $this->capacity->currentContract($tenant);
        if (! $contract) {
            throw ValidationException::withMessages([
                'vehicle' => 'Chỉ người đang ở trong một hợp đồng hoạt động mới được đăng ký phương tiện.',
            ]);
        }
        $allowedOwnerIds = $contract && $contract->isManagedBy($request->user())
            ? $contract->members()->where('status', ContractTenant::STATUS_CHECKED_IN)->whereNotNull('tenant_id')->pluck('tenant_id')
            : collect([$tenant->id]);

        $ownerId = (int) ($request->input('owner_tenant_id') ?: $tenant->id);
        if (! $allowedOwnerIds->contains($ownerId)) {
            throw ValidationException::withMessages([
                'owner_tenant_id' => 'Chủ xe phải là người đang ở trong phòng.',
            ]);
        }

        return Tenant::query()->findOrFail($ownerId);
    }

    private function validatedVehicle(Request $request, ?Vehicle $vehicle = null): array
    {
        $data = $request->validate([
            'vehicle_type' => ['required', Rule::in(['motorcycle', 'electric_motorcycle', 'bicycle'])],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'license_plate' => [
                'nullable',
                'required_unless:vehicle_type,bicycle',
                'string',
                'max:50',
                Rule::unique('vehicles', 'license_plate')->ignore($vehicle?->id),
            ],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($data['vehicle_type'] === 'bicycle') {
            $data['license_plate'] = null;
        }

        unset($data['vehicle_image']);

        return $data;
    }
}
