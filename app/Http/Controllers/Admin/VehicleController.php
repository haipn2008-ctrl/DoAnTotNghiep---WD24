<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Room;
use App\Models\Vehicle;
use App\Services\VehicleCapacityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $allDeclarationMembers = ContractTenant::query()
            ->with(['tenant', 'contract.room'])
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->whereNotNull('tenant_id')
            ->whereHas('contract', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))
            ->latest('updated_at')
            ->get();
        $declarationMembers = $allDeclarationMembers
            ->groupBy('tenant_id')
            ->map(function ($members) {
                $member = $members->first();
                $member->setAttribute('active_room_codes', $members->pluck('contract.room.room_code')->filter()->unique()->values());
                $member->setAttribute('active_contract_codes', $members->pluck('contract.contract_code')->filter()->unique()->values());
                $status = $members->contains(fn ($item) => $item->vehicle_declaration_status === ContractTenant::VEHICLE_HAS)
                    ? ContractTenant::VEHICLE_HAS
                    : ($members->contains(fn ($item) => $item->vehicle_declaration_status === ContractTenant::VEHICLE_LATER)
                        ? ContractTenant::VEHICLE_LATER
                        : ($members->every(fn ($item) => $item->vehicle_declaration_status === ContractTenant::VEHICLE_NONE)
                            ? ContractTenant::VEHICLE_NONE
                            : ContractTenant::VEHICLE_UNDECLARED));
                $member->setAttribute('vehicle_declaration_status', $status);
                return $member;
            })->values();
        $declarationCounts = [
            'undeclared' => $declarationMembers->where('vehicle_declaration_status', ContractTenant::VEHICLE_UNDECLARED)->count(),
            'later' => $declarationMembers->where('vehicle_declaration_status', ContractTenant::VEHICLE_LATER)->count(),
            'no_vehicle' => $declarationMembers->where('vehicle_declaration_status', ContractTenant::VEHICLE_NONE)->count(),
            'has_vehicle' => $declarationMembers->where('vehicle_declaration_status', ContractTenant::VEHICLE_HAS)->count(),
        ];

        return view('admin.vehicles.index', compact(
            'vehicles', 'rooms', 'counts', 'search', 'status', 'roomId',
            'declarationMembers', 'declarationCounts'
        ));
    }

    public function store(Request $request, VehicleCapacityService $capacity): RedirectResponse
    {
        $data = $request->validate([
            'contract_tenant_id' => ['required', 'integer', Rule::exists('contract_tenants', 'id')],
            'vehicle_type' => ['required', Rule::in(['motorcycle', 'electric_motorcycle', 'bicycle'])],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'license_plate' => ['nullable', 'required_unless:vehicle_type,bicycle', 'string', 'max:50', Rule::unique('vehicles', 'license_plate')],
            'color' => ['nullable', 'string', 'max:100'],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $member = ContractTenant::query()->with(['tenant', 'contract'])
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->whereHas('contract', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))
            ->findOrFail($data['contract_tenant_id']);
        abort_unless($member->tenant, 422, 'Người thuê chưa có hồ sơ tài khoản để đăng ký phương tiện.');
        $imagePath = $request->file('vehicle_image')?->store('vehicles', 'local');

        try {
            DB::transaction(function () use ($member, $data, $imagePath, $request, $capacity): void {
                $capacity->ensureCanSubmit($member->tenant);
                $member->tenant->vehicles()->create([
                    'vehicle_type' => $data['vehicle_type'],
                    'vehicle_name' => $data['vehicle_name'] ?? null,
                    'license_plate' => $data['vehicle_type'] === 'bicycle' ? null : strtoupper(trim($data['license_plate'])),
                    'color' => $data['color'] ?? null,
                    'vehicle_image' => $imagePath,
                    'status' => Vehicle::STATUS_APPROVED,
                    'submitted_by' => $request->user()->id,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'review_note' => 'Quản lý đăng ký hộ khách thuê.',
                ]);
                ContractTenant::query()->where('tenant_id', $member->tenant_id)
                    ->where('status', ContractTenant::STATUS_CHECKED_IN)
                    ->whereHas('contract', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))->update([
                    'vehicle_declaration_status' => ContractTenant::VEHICLE_HAS,
                    'vehicle_declared_at' => now(),
                    'vehicle_declared_by' => $request->user()->id,
                ]);
            }, 3);
        } catch (\Throwable $exception) {
            if ($imagePath) {
                Storage::disk('local')->delete($imagePath);
            }
            throw $exception;
        }

        return back()->with('success', 'Đã thêm và duyệt phương tiện hộ khách thuê.');
    }
}
