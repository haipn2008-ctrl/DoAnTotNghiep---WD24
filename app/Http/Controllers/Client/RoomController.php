<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\ContractTenantHistory;
use App\Models\TemporaryResidence;
use App\Rules\AdultDateOfBirth;
use App\Services\ContractIdentityDocumentService;
use App\Services\TenantIdentityDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomController extends Controller
{
    public function __construct(
        private readonly ContractIdentityDocumentService $contractIdentityDocuments,
        private readonly TenantIdentityDocumentService $tenantIdentityDocuments,
    ) {}

    public function show(Request $request): View
    {
        $contracts = $request->user()->tenant?->contracts()
            ->with([
                'room.amenities',
                'room.images' => fn ($query) => $query
                    ->with('uploader')
                    ->where('disk', 'public')
                    ->latest('taken_at')
                    ->latest('id'),
            ])
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES)
            ->latest('start_date')
            ->latest('id')
            ->get() ?? collect();

        return view('client.room.show', [
            'contracts' => $contracts,
        ]);
    }

    public function members(Request $request): View
    {
        $contract = $this->currentContract($request, [
            'room',
            'members' => fn ($query) => $query
                ->whereIn('status', [
                    ContractTenant::STATUS_CHECKED_IN,
                    ContractTenant::STATUS_MOVED_OUT,
                ])
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [ContractTenant::STATUS_CHECKED_IN])
                ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [ContractTenant::ROLE_REPRESENTATIVE])
                ->orderByDesc('actual_move_in_at')
                ->orderBy('full_name'),
        ]);

        $members = $contract?->members ?? collect();

        return view('client.room.members', [
            'contract' => $contract,
            'room' => $contract?->room,
            'members' => $members->where('status', ContractTenant::STATUS_CHECKED_IN)->values(),
            'formerMembers' => $members->where('status', ContractTenant::STATUS_MOVED_OUT)->values(),
        ]);
    }

    public function member(Request $request, int $member): View
    {
        $contract = $this->currentContract($request);

        abort_unless($contract, 404);

        $member = $this->findCurrentMember($contract, $member);
        $member->load([
            'temporaryResidences' => fn ($query) => $query
                ->latest('created_at')
                ->latest('id'),
        ]);
        $availableResidenceEvidenceIds = $member->temporaryResidences
            ->filter(fn (TemporaryResidence $residence) => $residence->evidence_path
                && Storage::disk('local')->exists($residence->evidence_path))
            ->modelKeys();
        $availableIdentitySides = collect([
            'front' => $member->identity_front_path,
            'back' => $member->identity_back_path,
        ])->filter(fn (?string $path) => $path && Storage::disk('local')->exists($path))->keys()->all();

        return view('client.room.member', [
            'contract' => $contract,
            'room' => $contract->room,
            'member' => $member,
            'availableResidenceEvidenceIds' => $availableResidenceEvidenceIds,
            'availableIdentitySides' => $availableIdentitySides,
        ]);
    }

    public function memberIdentity(Request $request, int $member, string $side): StreamedResponse
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);

        $contract = $this->currentContract($request);
        abort_unless($contract, 404);

        $member = $this->findCurrentMember($contract, $member);
        $path = $side === 'front' ? $member->identity_front_path : $member->identity_back_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function memberTemporaryResidenceEvidence(
        Request $request,
        int $member,
        TemporaryResidence $temporaryResidence
    ): StreamedResponse {
        $contract = $this->currentContract($request);
        abort_unless($contract, 404);

        $member = $this->findCurrentMember($contract, $member);
        $residence = $member->temporaryResidences()->findOrFail($temporaryResidence->id);

        abort_unless(
            $residence->evidence_path
                && Storage::disk('local')->exists($residence->evidence_path),
            404
        );

        return Storage::disk('local')->response(
            $residence->evidence_path,
            $residence->evidence_original_name,
            [
                'Content-Type' => $residence->evidence_mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function updateMember(Request $request, int $member): RedirectResponse
    {
        $contract = $this->currentContract($request);
        abort_unless($contract, 404);

        $member = $this->findCurrentMember($contract, $member);
        $tenant = $member->tenant;
        abort_unless($tenant, 409, 'Thành viên chưa có hồ sơ khách thuê.');

        $isRepresentative = $member->role === ContractTenant::ROLE_REPRESENTATIVE;
        $identityChanged = (string) $request->input('identity_number') !== (string) $member->identity_number;
        $identityRequired = $identityChanged || $request->hasFile('identity_front') || $request->hasFile('identity_back');

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'identity_number' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenant->id)],
            'cccd_issue_date' => ['required', 'date', 'before_or_equal:today', 'after:date_of_birth'],
            'cccd_issue_place' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'phone')->ignore($tenant->user_id),
                Rule::unique('tenants', 'phone')->ignore($tenant->id),
            ],
            'address' => ['required', 'string', 'max:500'],
            'identity_front' => [Rule::requiredIf($identityRequired), 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => [Rule::requiredIf($identityRequired), 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
        if ($isRepresentative) {
            $rules['email'] = [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($tenant->user_id),
                Rule::unique('tenants', 'email')->ignore($tenant->id),
            ];
        }

        $data = $request->validate($rules, [
            'identity_front.required' => 'Vui lòng tải ảnh mặt trước CCCD khi thay đổi giấy tờ.',
            'identity_back.required' => 'Vui lòng tải ảnh mặt sau CCCD khi thay đổi giấy tờ.',
            'identity_front.image' => 'Mặt trước CCCD phải là tệp ảnh.',
            'identity_back.image' => 'Mặt sau CCCD phải là tệp ảnh.',
            'identity_front.mimes' => 'Ảnh CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'identity_back.mimes' => 'Ảnh CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $contract, $member, $data, $isRepresentative, &$storedPaths): void {
                $member = $contract->members()
                    ->where('status', ContractTenant::STATUS_CHECKED_IN)
                    ->lockForUpdate()
                    ->findOrFail($member->id);
                $tenant = $member->tenant()->lockForUpdate()->firstOrFail();

                $tenantData = [
                    'full_name' => $data['full_name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],
                    'cccd' => $data['identity_number'],
                    'cccd_issue_date' => $data['cccd_issue_date'],
                    'cccd_issue_place' => $data['cccd_issue_place'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                ];
                if ($isRepresentative) {
                    $tenantData['email'] = $data['email'];
                    $tenant->user()->lockForUpdate()->first()?->update([
                        'name' => $data['full_name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                    ]);
                }
                $tenant->update($tenantData);

                $member->forceFill([
                    'full_name' => $data['full_name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'identity_number' => $data['identity_number'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                ])->save();

                if (isset($data['identity_front'], $data['identity_back'])) {
                    $this->contractIdentityDocuments->storePair(
                        $member,
                        $data['identity_front'],
                        $data['identity_back'],
                        $request->user(),
                        $storedPaths,
                    );
                } else {
                    $this->tenantIdentityDocuments->syncMetadata($tenant->fresh());
                }

                ContractTenantHistory::query()->create([
                    'contract_tenant_id' => $member->id,
                    'from_status' => $member->status,
                    'to_status' => $member->status,
                    'action' => 'profile_updated_by_representative',
                    'performed_by' => $request->user()->id,
                    'performed_at' => now(),
                ]);
            });
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($storedPaths);
            report($exception);

            throw ValidationException::withMessages([
                'identity_number' => 'CCCD, email hoặc số điện thoại đã được sử dụng.',
                'phone' => 'CCCD, email hoặc số điện thoại đã được sử dụng.',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Đã cập nhật thông tin thành viên.');
    }

    private function currentContract(Request $request, array $with = ['room']): ?Contract
    {
        $query = $request->user()->tenant?->contracts()
            ->with($with)
            ->whereIn('status', Contract::OPEN_OCCUPANCY_STATUSES);
        if (! $query) {
            return null;
        }

        if ($contractId = $request->integer('contract')) {
            return $query->whereKey($contractId)->firstOrFail();
        }

        return $query->latest('start_date')->latest('id')->first();
    }

    private function findCurrentMember(Contract $contract, int $member): ContractTenant
    {
        return $contract->members()
            ->with('tenant.user')
            ->where('status', ContractTenant::STATUS_CHECKED_IN)
            ->findOrFail($member);
    }
}
