<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class VehicleController extends Controller
{
    public function __construct(private readonly AdminNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant()->with(['vehicles.reviewer'])->firstOrFail();

        return view('client.vehicles.index', compact('tenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant()->firstOrFail();
        $data = $this->validatedVehicle($request);
        $imagePath = $request->file('vehicle_image')?->store('vehicles', 'public');

        try {
            $vehicle = $tenant->vehicles()->create($data + [
                'vehicle_image' => $imagePath,
                'status' => Vehicle::STATUS_PENDING,
                'submitted_by' => $request->user()->id,
            ]);
            $this->notifications->vehicleSubmitted($vehicle);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            throw $exception;
        }

        return back()->with('success', 'Đã gửi phương tiện để quản trị viên duyệt.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle = $this->ownedVehicle($request, $vehicle);
        abort_if($vehicle->status === Vehicle::STATUS_PENDING, 409, 'Yêu cầu đang chờ duyệt. Hãy hủy yêu cầu nếu cần đăng ký lại.');
        $data = $this->validatedVehicle($request, $vehicle);
        $oldImagePath = $vehicle->vehicle_image;
        $newImagePath = $request->file('vehicle_image')?->store('vehicles', 'public');

        try {
            $vehicle->update($data + [
                'vehicle_image' => $newImagePath ?: $oldImagePath,
                'status' => Vehicle::STATUS_PENDING,
                'submitted_by' => $request->user()->id,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
            ]);
            $this->notifications->vehicleSubmitted($vehicle, true);
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return back()->with('success', 'Đã cập nhật và gửi lại phương tiện để duyệt.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle = $this->ownedVehicle($request, $vehicle);
        $imagePath = $vehicle->vehicle_image;
        $wasApproved = $vehicle->status === Vehicle::STATUS_APPROVED;

        if ($wasApproved) {
            $this->notifications->vehicleRemoved($vehicle);
        } else {
            $this->notifications->vehicleRequestCancelled($vehicle);
        }

        $vehicle->delete();
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return back()->with('success', $wasApproved
            ? 'Đã gỡ phương tiện.'
            : 'Đã hủy yêu cầu. Bạn có thể đăng ký lại phương tiện từ đầu.');
    }

    private function ownedVehicle(Request $request, Vehicle $vehicle): Vehicle
    {
        abort_unless((int) $vehicle->tenant_id === (int) $request->user()->tenant?->id, 404);

        return $vehicle;
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
