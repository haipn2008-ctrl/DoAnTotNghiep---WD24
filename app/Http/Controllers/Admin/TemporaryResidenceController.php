<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\TemporaryResidence;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TemporaryResidenceController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = ContractTenant::query()
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->whereHas('contract', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES));
        $query = (clone $baseQuery)->with([
            'tenant', 'contract.room', 'activeTemporaryResidence.verifiedBy', 'latestTemporaryResidence.verifiedBy',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($query) use ($search): void {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%")
                    ->orWhereHas('contract.room', fn ($room) => $room->where('room_code', 'like', "%{$search}%"))
                    ->orWhereHas('temporaryResidences', fn ($residence) => $residence
                        ->where('reference_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'missing') {
                $query->whereDoesntHave('temporaryResidences', fn ($residence) => $residence
                    ->where('status', 'active')->whereNotNull('evidence_path'));
            } elseif (in_array($status, ['pending', 'active'], true)) {
                $query->whereHas('temporaryResidences', fn ($residence) => $residence
                    ->where('status', $status)
                    ->when($status === 'active', fn ($query) => $query->whereNotNull('evidence_path')));
            } elseif (in_array($status, ['expired', 'cancelled'], true)) {
                $query->whereDoesntHave('temporaryResidences', fn ($residence) => $residence->whereIn('status', ['pending', 'active']))
                    ->whereHas('temporaryResidences', fn ($residence) => $residence->where('status', $status));
            }
        }

        $members = $query->orderBy('full_name')->orderBy('id')->paginate(15)->withQueryString();
        $summary = [
            'total' => (clone $baseQuery)->count(),
            'documented' => (clone $baseQuery)->whereHas('temporaryResidences', fn ($query) => $query
                ->where('status', 'active')->whereNotNull('evidence_path'))->count(),
            'missing' => (clone $baseQuery)->whereDoesntHave('temporaryResidences', fn ($query) => $query
                ->where('status', 'active')->whereNotNull('evidence_path'))->count(),
        ];

        return view('admin.temporary_residences.index', compact('members', 'summary'));
    }

    public function create()
    {
        $members = ContractTenant::query()
            ->with(['tenant', 'contract.room'])
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->whereHas('contract', fn ($query) => $query->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES))
            ->whereDoesntHave('temporaryResidences', fn ($query) => $query->whereIn('status', ['pending', 'active']))
            ->orderBy('full_name')
            ->get();

        return view('admin.temporary_residences.create', compact('members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_tenant_id' => [
                'required',
                Rule::exists('contract_tenants', 'id')->where('status', ContractTenant::STATUS_CHECKED_IN),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'contract_tenant_id.required' => 'Vui lòng chọn người thuê cần cập nhật giấy tạm trú.',
            'contract_tenant_id.exists' => 'Người được chọn không còn ở trong hợp đồng.',
            'evidence.required' => 'Vui lòng tải ảnh hoặc PDF minh chứng giấy tạm trú.',
            'evidence.mimes' => 'Minh chứng chỉ chấp nhận JPG, PNG, WEBP hoặc PDF.',
            'evidence.max' => 'Minh chứng không được vượt quá 5 MB.',
        ]);

        $member = ContractTenant::query()->with(['tenant', 'contract'])->findOrFail($validated['contract_tenant_id']);
        if (! in_array($member->contract->status, Contract::OPEN_OCCUPANCY_STATUSES, true)) {
            throw ValidationException::withMessages(['contract_tenant_id' => 'Hợp đồng của người thuê không còn hiệu lực cư trú.']);
        }
        $file = $request->file('evidence');
        $path = $file->store("temporary-residences/{$member->id}", 'local');

        try {
            $temporaryResidence = DB::transaction(function () use ($member, $validated, $path, $file, $request): TemporaryResidence {
                $lockedMember = ContractTenant::query()->lockForUpdate()->findOrFail($member->id);
                if ($lockedMember->status !== ContractTenant::STATUS_CHECKED_IN) {
                    throw ValidationException::withMessages(['contract_tenant_id' => 'Người được chọn không còn ở trong hợp đồng.']);
                }
                if (TemporaryResidence::query()->where('contract_tenant_id', $lockedMember->id)
                    ->whereIn('status', ['pending', 'active'])->exists()) {
                    throw ValidationException::withMessages(['contract_tenant_id' => 'Người thuê này đã có giấy tạm trú đang hiệu lực.']);
                }

                return TemporaryResidence::query()->create([
                    'tenant_id' => $lockedMember->tenant_id,
                    'contract_id' => $lockedMember->contract_id,
                    'room_id' => $lockedMember->contract->room_id,
                    'contract_tenant_id' => $lockedMember->id,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'status' => filled($validated['end_date'] ?? null) && Carbon::parse($validated['end_date'])->lt(today())
                        ? 'expired'
                        : 'active',
                    'note' => $validated['note'] ?? null,
                    'evidence_path' => $path,
                    'evidence_original_name' => $file->getClientOriginalName(),
                    'evidence_mime_type' => $file->getMimeType(),
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('admin.temporary_residences.show', $temporaryResidence)
            ->with('success', 'Đã cập nhật và xác minh minh chứng giấy tạm trú cho người thuê.');
    }

    public function show(TemporaryResidence $temporaryResidence)
    {
        $temporaryResidence->load([
            'tenant.document', 'room', 'contract.room', 'contractTenant', 'verifiedBy', 'cancelledBy',
        ]);
        $residenceHistory = TemporaryResidence::query()
            ->where('contract_tenant_id', $temporaryResidence->contract_tenant_id)
            ->when(! $temporaryResidence->contract_tenant_id, fn ($query) => $query
                ->where('contract_id', $temporaryResidence->contract_id)
                ->where('tenant_id', $temporaryResidence->tenant_id))
            ->latest()
            ->get();

        return view('admin.temporary_residences.show', compact('temporaryResidence', 'residenceHistory'));
    }

    public function edit(TemporaryResidence $temporaryResidence)
    {
        $this->ensureMutable($temporaryResidence);
        $temporaryResidence->load(['tenant', 'room', 'contract.room', 'contractTenant']);

        return view('admin.temporary_residences.edit', compact('temporaryResidence'));
    }

    public function update(Request $request, TemporaryResidence $temporaryResidence)
    {
        $this->ensureMutable($temporaryResidence);
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('evidence');
        $newPath = $file?->store("temporary-residences/{$temporaryResidence->contract_tenant_id}", 'local');
        $oldPath = $temporaryResidence->evidence_path;

        try {
            DB::transaction(function () use ($temporaryResidence, $validated, $request, $file, $newPath): void {
                $lockedResidence = TemporaryResidence::query()->lockForUpdate()->findOrFail($temporaryResidence->id);
                $this->ensureMutable($lockedResidence);
                $changes = [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'note' => $validated['note'] ?? null,
                    'status' => filled($validated['end_date'] ?? null) && Carbon::parse($validated['end_date'])->lt(today())
                        ? 'expired'
                        : 'active',
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ];
                if ($file && $newPath) {
                    $changes += [
                        'evidence_path' => $newPath,
                        'evidence_original_name' => $file->getClientOriginalName(),
                        'evidence_mime_type' => $file->getMimeType(),
                    ];
                }
                $lockedResidence->update($changes);
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath && $oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('admin.temporary_residences.show', $temporaryResidence)
            ->with('success', 'Giấy tạm trú đã được cập nhật.');
    }

    public function cancel(Request $request, TemporaryResidence $temporaryResidence)
    {
        $this->ensureMutable($temporaryResidence);
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($temporaryResidence, $validated, $request): void {
            $lockedResidence = TemporaryResidence::query()->lockForUpdate()->findOrFail($temporaryResidence->id);
            $this->ensureMutable($lockedResidence);
            $lockedResidence->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancellation_reason' => $validated['cancellation_reason'],
            ]);
        });

        return redirect()->route('admin.temporary_residences.index')
            ->with('success', 'Giấy tạm trú đã được hủy và vẫn được lưu để truy vết.');
    }

    public function evidence(TemporaryResidence $temporaryResidence)
    {
        abort_unless(
            $temporaryResidence->evidence_path
                && Storage::disk('local')->exists($temporaryResidence->evidence_path),
            404
        );

        return Storage::disk('local')->response(
            $temporaryResidence->evidence_path,
            $temporaryResidence->evidence_original_name,
            ['Content-Type' => $temporaryResidence->evidence_mime_type ?: 'application/octet-stream']
        );
    }

    public function updateEvidence(Request $request, TemporaryResidence $temporaryResidence)
    {
        if ($temporaryResidence->status === 'cancelled') {
            throw ValidationException::withMessages([
                'temporary_residence' => 'Giấy tạm trú đã hủy không thể bổ sung minh chứng.',
            ]);
        }
        $request->validate([
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'evidence.required' => 'Vui lòng chọn ảnh hoặc PDF minh chứng.',
            'evidence.mimes' => 'Minh chứng chỉ chấp nhận JPG, PNG, WEBP hoặc PDF.',
            'evidence.max' => 'Minh chứng không được vượt quá 5 MB.',
        ]);

        $file = $request->file('evidence');
        $newPath = $file->store("temporary-residences/{$temporaryResidence->contract_tenant_id}", 'local');
        $oldPath = $temporaryResidence->evidence_path;

        try {
            DB::transaction(function () use ($temporaryResidence, $request, $file, $newPath): void {
                $lockedResidence = TemporaryResidence::query()->lockForUpdate()->findOrFail($temporaryResidence->id);
                if ($lockedResidence->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'temporary_residence' => 'Giấy tạm trú đã hủy không thể bổ sung minh chứng.',
                    ]);
                }
                $lockedResidence->forceFill([
                    'evidence_path' => $newPath,
                    'evidence_original_name' => $file->getClientOriginalName(),
                    'evidence_mime_type' => $file->getMimeType(),
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ])->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Đã cập nhật minh chứng giấy tạm trú.');
    }

    public function sign(Request $request, TemporaryResidence $temporaryResidence)
    {
        $this->ensureMutable($temporaryResidence);
        $validated = $request->validate([
            'signature' => [
                'bail', 'required', 'string', 'max:1500000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
                        $fail('Chữ ký phải là ảnh PNG hợp lệ.');

                        return;
                    }
                    $decoded = base64_decode($matches[1], true);
                    $imageInfo = $decoded === false ? false : @getimagesizefromstring($decoded);
                    if ($decoded === false || $imageInfo === false || ($imageInfo[2] ?? null) !== IMAGETYPE_PNG) {
                        $fail('Chữ ký phải là ảnh PNG hợp lệ.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($temporaryResidence, $validated): void {
            $lockedResidence = TemporaryResidence::query()->lockForUpdate()->findOrFail($temporaryResidence->id);
            $this->ensureMutable($lockedResidence);
            $lockedResidence->update(['signature' => $validated['signature'], 'signed_at' => now()]);
        });

        return back()->with('success', 'Chữ ký đã được lưu thành công.');
    }

    public function pdf(TemporaryResidence $temporaryResidence)
    {
        $temporaryResidence->load(['tenant.document', 'tenant.vehicles', 'room', 'contract.room']);

        return view('admin.temporary_residences.pdf', compact('temporaryResidence'));
    }

    private function ensureMutable(TemporaryResidence $temporaryResidence): void
    {
        if ($temporaryResidence->status === 'cancelled') {
            throw ValidationException::withMessages([
                'temporary_residence' => 'Giấy tạm trú đã hủy không thể thay đổi.',
            ]);
        }
        if ($temporaryResidence->signature || $temporaryResidence->signed_at) {
            throw ValidationException::withMessages([
                'temporary_residence' => 'Hồ sơ đã ký không thể sửa, ký đè hoặc hủy.',
            ]);
        }
    }
}
