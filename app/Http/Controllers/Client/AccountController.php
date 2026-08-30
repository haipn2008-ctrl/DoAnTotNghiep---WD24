<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\TemporaryResidence;
use App\Rules\AdultDateOfBirth;
use App\Services\TenantIdentityDocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function __construct(private readonly TenantIdentityDocumentService $identityDocuments) {}

    public function edit(Request $request): View
    {
        $user = $request->user()->load([
            'tenant.document',
            'tenant.temporaryResidences' => fn ($query) => $query
                ->with('contract.room')
                ->latest('created_at')
                ->latest('id'),
        ]);
        $availableResidenceEvidenceIds = $user->tenant?->temporaryResidences
            ->filter(fn (TemporaryResidence $residence) => $residence->evidence_path
                && Storage::disk('local')->exists($residence->evidence_path))
            ->modelKeys() ?? [];

        return view('client.account.edit', [
            'user' => $user,
            'availableResidenceEvidenceIds' => $availableResidenceEvidenceIds,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;
        abort_unless($tenant, 409, 'Tài khoản chưa có hồ sơ khách thuê.');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'date_of_birth' => ['required', 'date', new AdultDateOfBirth],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'cccd' => ['required', 'digits:12', Rule::unique('tenants', 'cccd')->ignore($tenant->id)],
            'cccd_issue_date' => ['required', 'date', 'before_or_equal:today', 'after:date_of_birth'],
            'cccd_issue_place' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
                Rule::unique('tenants', 'email')->ignore($tenant->id),
            ],
            'phone' => [
                'required', 'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
                Rule::unique('tenants', 'phone')->ignore($tenant->id),
            ],
            'address' => ['required', 'string', 'max:500'],
            'identity_front' => ['nullable', 'required_with:identity_back', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'identity_back' => ['nullable', 'required_with:identity_front', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'identity_front.required_with' => 'Vui lòng tải đủ ảnh mặt trước CCCD.',
            'identity_back.required_with' => 'Vui lòng tải đủ ảnh mặt sau CCCD.',
            'identity_front.image' => 'Mặt trước CCCD phải là tệp ảnh.',
            'identity_back.image' => 'Mặt sau CCCD phải là tệp ảnh.',
            'identity_front.mimes' => 'Ảnh CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'identity_back.mimes' => 'Ảnh CCCD chỉ chấp nhận JPG, PNG hoặc WEBP.',
            'identity_front.max' => 'Ảnh CCCD không được lớn hơn 5 MB.',
            'identity_back.max' => 'Ảnh CCCD không được lớn hơn 5 MB.',
        ]);

        $storedPaths = [];
        try {
            DB::transaction(function () use ($user, $data, &$storedPaths): void {
                $lockedUser = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
                $tenant = $lockedUser->tenant()->lockForUpdate()->first();
                $lockedUser->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ]);
                $tenant?->update([
                    'full_name' => $data['name'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],
                    'cccd' => $data['cccd'],
                    'cccd_issue_date' => $data['cccd_issue_date'],
                    'cccd_issue_place' => $data['cccd_issue_place'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                ]);
                if ($tenant) {
                    isset($data['identity_front'], $data['identity_back'])
                        ? $this->identityDocuments->storePair($tenant->fresh(), $data['identity_front'], $data['identity_back'], $storedPaths)
                        : $this->identityDocuments->syncMetadata($tenant->fresh());
                }
            });
        } catch (QueryException $exception) {
            Storage::disk('local')->delete($storedPaths);
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'Email hoặc số điện thoại đã được sử dụng.',
                'phone' => 'Email hoặc số điện thoại đã được sử dụng.',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return back()->with('success', 'Đã cập nhật hồ sơ cá nhân và thông tin tài khoản.');
    }

    public function identityDocument(Request $request, string $side): StreamedResponse
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $document = $request->user()->tenant?->document;
        $path = $document?->imagePath($side);
        abort_unless($path && $document->hasImage($side), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function temporaryResidenceEvidence(
        Request $request,
        TemporaryResidence $temporaryResidence
    ): StreamedResponse {
        abort_unless(
            $request->user()->tenant?->id === $temporaryResidence->tenant_id,
            404
        );
        abort_unless(
            $temporaryResidence->evidence_path
                && Storage::disk('local')->exists($temporaryResidence->evidence_path),
            404
        );

        return Storage::disk('local')->response(
            $temporaryResidence->evidence_path,
            $temporaryResidence->evidence_original_name,
            [
                'Content-Type' => $temporaryResidence->evidence_mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'different:current_password', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
