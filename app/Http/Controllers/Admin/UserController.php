<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Contract;
use App\Models\ContractTenant;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    protected function adminOnly(): void
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->adminOnly();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([
                User::STATUS_PENDING,
                User::STATUS_ACTIVE,
                User::STATUS_SETTLING,
                User::STATUS_FORMER,
                User::STATUS_LOCKED,
                User::STATUS_INACTIVE,
            ])],
        ]);
        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $users = User::with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'status'));
    }

    public function create()
    {
        $this->adminOnly();

        $roles = $this->manageableRoles();

        return view('admin.users.create', compact('roles'));
    }

    public function store(UserRequest $request)
    {
        $this->adminOnly();

        $data = $request->validated();
        $role = $this->findManageableRole($data['role_id']);
        $data['status'] = $this->isClientRole($role)
            ? User::STATUS_PENDING
            : User::STATUS_ACTIVE;
        $data['must_change_password'] = $this->isClientRole($role);
        $data['activated_at'] = $data['status'] === User::STATUS_ACTIVE ? now() : null;
        $initialPassword = $data['password'];

        try {
            $user = User::create($data);
        } catch (QueryException $exception) {
            $this->throwEmailConflictOrRethrow($exception);
        }

        if ($this->isClientRole($role)) {
            try {
                $user->notify(new AccountCreatedNotification($initialPassword));
            } catch (Throwable $exception) {
                Log::error('Không thể gửi email thông tin tài khoản khách thuê.', [
                    'user_id' => $user->id,
                    'exception' => $exception,
                ]);

                return redirect()
                    ->route('admin.users.index')
                    ->with('error', 'Tài khoản đã được tạo nhưng chưa thể gửi email. Vui lòng kiểm tra cấu hình email và thử lại.');
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $this->isClientRole($role)
                ? 'Tạo tài khoản thành công. Thông tin đăng nhập đã được gửi đến email của khách thuê.'
                : 'Tạo tài khoản thành công.');
    }

    public function edit(User $user)
    {
        $this->adminOnly();

        $roles = $this->manageableRoles();
        $statuses = $this->allowedStatusesFor($user);

        return view('admin.users.edit', compact('user', 'roles', 'statuses'));
    }

    public function update(UserRequest $request, User $user)
    {
        $this->adminOnly();

        $data = $request->validated();
        $newRole = $this->findManageableRole($data['role_id']);

        if ($user->is($request->user()) && (
            (int) $data['role_id'] !== (int) $user->role_id
            || $data['status'] !== $user->status
        )) {
            throw ValidationException::withMessages([
                'status' => 'Bạn không thể tự thay đổi vai trò hoặc trạng thái tài khoản của mình.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $currentIsClient = $user->role ? $this->isClientRole($user->role) : null;
        $newIsClient = $this->isClientRole($newRole);

        if ($currentIsClient !== $newIsClient) {
            $data['status'] = $newIsClient ? User::STATUS_PENDING : User::STATUS_ACTIVE;
        } else {
            $this->validateRequestedStatus($user, $newRole, $data['status']);
        }

        if (in_array($data['status'], [User::STATUS_ACTIVE, User::STATUS_SETTLING, User::STATUS_FORMER, User::STATUS_INACTIVE], true)) {
            $data['activated_at'] = $user->activated_at ?? now();
            $data['must_change_password'] = false;
        } elseif ($data['status'] === User::STATUS_PENDING) {
            $data['activated_at'] = null;
            $data['must_change_password'] = true;
        }

        if ($data['status'] === User::STATUS_INACTIVE && $user->status !== User::STATUS_INACTIVE) {
            $data['deactivated_at'] = now();
            $data['deactivated_by'] = $request->user()->id;
            $data['deactivation_reason'] = 'Ngừng sử dụng từ màn hình cập nhật tài khoản.';
        } elseif ($data['status'] !== User::STATUS_INACTIVE) {
            $data['deactivated_at'] = null;
            $data['deactivated_by'] = null;
            $data['deactivation_reason'] = null;
        }

        try {
            $user->update($data);
            if ($data['status'] === User::STATUS_INACTIVE) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        } catch (QueryException $exception) {
            $this->throwEmailConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->adminOnly();

        if (Auth::id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể ngừng sử dụng chính tài khoản của bạn.');
        }

        $data = $request->validate([
            'deactivation_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($user->status === User::STATUS_INACTIVE) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Tài khoản đã ngừng sử dụng trước đó.');
        }

        $tenant = $user->tenant;
        $hasOpenContract = $tenant?->contracts()
            ->whereNotIn('status', [Contract::STATUS_COMPLETED, Contract::STATUS_CANCELLED])
            ->exists() ?? false;
        $hasOutstandingInvoice = $tenant
            ? Invoice::query()
                ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
                ->exists()
            : false;

        if ($hasOpenContract || $hasOutstandingInvoice) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể ngừng tài khoản đang có hợp đồng hoặc công nợ chưa hoàn tất.');
        }

        DB::transaction(function () use ($user, $request, $data): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->forceFill([
                'status' => User::STATUS_INACTIVE,
                'deactivated_at' => now(),
                'deactivated_by' => $request->user()->id,
                'deactivation_reason' => $data['deactivation_reason'],
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $lockedUser->id)->delete();
        }, 3);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã ngừng sử dụng tài khoản và giữ nguyên lịch sử liên quan.');
    }

    public function restore(Request $request, User $user)
    {
        $this->adminOnly();

        $data = $request->validate([
            'reactivation_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($user->status !== User::STATUS_INACTIVE) {
            return back()->with('error', 'Chỉ có thể khôi phục tài khoản đang ngừng sử dụng.');
        }

        if ($user->tenant?->status === Tenant::STATUS_ARCHIVED) {
            return back()->with('error', 'Hãy khôi phục hồ sơ khách thuê để đồng bộ cả hồ sơ và tài khoản liên kết.');
        }

        DB::transaction(function () use ($user, $request, $data): void {
            $lockedUser = User::query()->with('tenant')->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->status !== User::STATUS_INACTIVE) {
                throw ValidationException::withMessages([
                    'user' => 'Trạng thái tài khoản đã thay đổi. Vui lòng tải lại trang.',
                ]);
            }

            $lockedUser->forceFill([
                'status' => $lockedUser->isClient() ? $this->expectedClientStatus($lockedUser) : User::STATUS_ACTIVE,
                'reactivated_at' => now(),
                'reactivated_by' => $request->user()->id,
                'reactivation_reason' => $data['reactivation_reason'],
                'activated_at' => $lockedUser->activated_at ?? now(),
                'must_change_password' => false,
            ])->save();
        }, 3);

        return back()->with('success', 'Đã khôi phục tài khoản và ghi nhận đầy đủ thông tin khôi phục.');
    }

    private function manageableRoles()
    {
        return Role::query()
            ->whereIn('role_name', ['Admin', 'User'])
            ->orderBy('id')
            ->get();
    }

    private function findManageableRole(int|string $roleId): Role
    {
        return Role::query()
            ->whereKey($roleId)
            ->whereIn('role_name', ['Admin', 'User'])
            ->firstOrFail();
    }

    private function isClientRole(Role $role): bool
    {
        return strtolower($role->role_name) === 'user';
    }

    private function allowedStatusesFor(User $user): array
    {
        if ($user->status === User::STATUS_INACTIVE) {
            return [User::STATUS_INACTIVE];
        }

        if ($user->isAdmin()) {
            return [User::STATUS_ACTIVE, User::STATUS_LOCKED];
        }

        return array_values(array_unique([
            User::STATUS_PENDING,
            $this->expectedClientStatus($user),
            User::STATUS_LOCKED,
        ]));
    }

    private function validateRequestedStatus(User $user, Role $role, string $status): void
    {
        $allowedStatuses = $this->isClientRole($role)
            ? $this->allowedStatusesFor($user)
            : [User::STATUS_ACTIVE, User::STATUS_LOCKED];

        if (! in_array($status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Trạng thái tài khoản không phù hợp với hợp đồng hoặc công nợ hiện tại.',
            ]);
        }
    }

    private function expectedClientStatus(User $user): string
    {
        $tenant = $user->tenant;

        if (! $tenant) {
            return User::STATUS_ACTIVE;
        }

        if ($tenant->contracts()->whereIn('status', [
            Contract::STATUS_PENDING_SIGNATURE,
            Contract::STATUS_PENDING_DEPOSIT,
            Contract::STATUS_AWAITING_MOVE_IN,
            Contract::STATUS_ACTIVE,
            Contract::STATUS_EXPIRED,
        ])->exists()) {
            return User::STATUS_ACTIVE;
        }

        $hasOutstandingInvoice = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->exists();

        $hasSettlement = $tenant->contracts()->where('status', Contract::STATUS_SETTLING)->exists();
        $hasRentalHistory = $tenant->contracts()
            ->where(function ($query): void {
                $query->whereNotNull('actual_move_in_at')
                    ->orWhereIn('status', [Contract::STATUS_SETTLING, Contract::STATUS_COMPLETED]);
            })
            ->exists()
            || $tenant->memberContracts()
                ->wherePivotIn('status', [
                    ContractTenant::STATUS_CHECKED_IN,
                    ContractTenant::STATUS_MOVED_OUT,
                ])->exists();

        return $hasOutstandingInvoice || $hasSettlement
            ? User::STATUS_SETTLING
            : ($hasRentalHistory ? User::STATUS_FORMER : User::STATUS_ACTIVE);
    }

    private function throwEmailConflictOrRethrow(QueryException $exception): never
    {
        $message = strtolower($exception->getMessage());
        $isEmailConflict = str_contains($message, 'users.email')
            || str_contains($message, 'users_email_unique')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'email'));

        if (! $isEmailConflict) {
            throw $exception;
        }

        throw ValidationException::withMessages([
            'email' => 'Email đã được sử dụng bởi tài khoản khác.',
        ]);
    }
}
