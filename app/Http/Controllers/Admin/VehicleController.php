<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Room;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                'current', 'all', Vehicle::STATUS_PENDING, Vehicle::STATUS_APPROVED,
                Vehicle::STATUS_REJECTED, Vehicle::STATUS_CANCELLED, Vehicle::STATUS_REMOVED,
            ])],
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')],
        ]);

        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'current';
        $roomId = isset($filters['room_id']) ? (int) $filters['room_id'] : null;

        $query = Vehicle::query()
            ->with([
                'tenant.contracts' => fn ($query) => $query
                    ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
                    ->with('room'),
                'tenant.memberContracts' => fn ($query) => $query
                    ->whereIn('contracts.status', Contract::OPEN_OCCUPANCY_STATUSES)
                    ->where('contract_tenants.status', ContractTenant::STATUS_CHECKED_IN)
                    ->with('room'),
                'reviewer',
            ])
            ->when($status === 'current', fn ($query) => $query->whereIn('status', [
                Vehicle::STATUS_PENDING,
                Vehicle::STATUS_APPROVED,
            ]))
            ->when(! in_array($status, ['current', 'all'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('license_plate', 'like', "%{$search}%")
                        ->orWhere('archived_license_plate', 'like', "%{$search}%")
                        ->orWhere('vehicle_name', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn ($query) => $query
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('tenant.contracts.room', fn ($query) => $query
                            ->where('room_code', 'like', "%{$search}%"))
                        ->orWhereHas('tenant.memberContracts.room', fn ($query) => $query
                            ->where('room_code', 'like', "%{$search}%"));
                });
            })
            ->when($roomId, fn ($query) => $query->whereHas('tenant', function ($query) use ($roomId): void {
                $query->where(function ($query) use ($roomId): void {
                    $query->whereHas('contracts', fn ($query) => $query
                        ->where('room_id', $roomId)
                        ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))
                        ->orWhereHas('memberContracts', fn ($query) => $query
                            ->where('contracts.room_id', $roomId)
                            ->whereIn('contracts.status', Contract::OPEN_OCCUPANCY_STATUSES)
                            ->where('contract_tenants.status', ContractTenant::STATUS_CHECKED_IN));
                });
            }));

        $vehicles = $query
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $counts = Vehicle::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $counts['current'] = (int) ($counts[Vehicle::STATUS_PENDING] ?? 0)
            + (int) ($counts[Vehicle::STATUS_APPROVED] ?? 0);
        $counts['all'] = $counts->except(['current', 'all'])->sum();

        $rooms = Room::query()->orderBy('room_code')->get(['id', 'room_code']);

        return view('admin.vehicles.index', compact(
            'vehicles', 'rooms', 'counts', 'search', 'status', 'roomId'
        ));
    }
}
