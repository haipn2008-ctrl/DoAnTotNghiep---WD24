<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        ]);
        $search = trim($validated['search'] ?? '');

        $users = User::with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
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

        try {
            User::create($data);
        } catch (QueryException $exception) {
            $this->throwEmailConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Tạo tài khoản thành công.');
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

        if (in_array($data['status'], [User::STATUS_ACTIVE, User::STATUS_SETTLING, User::STATUS_INACTIVE], true)) {
            $data['activated_at'] = $user->activated_at ?? now();
            $data['must_change_password'] = false;
        } elseif ($data['status'] === User::STATUS_PENDING) {
            $data['activated_at'] = null;
            $data['must_change_password'] = true;
        }

        try {
            $user->update($data);
        } catch (QueryException $exception) {
            $this->throwEmailConflictOrRethrow($exception);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(User $user)
    {
        $this->adminOnly();

        if (Auth::id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể xóa chính bạn.');
        }

        $tenant = $user->tenant;
        $hasOpenContract = $tenant?->contracts()
            ->whereIn('status', ['pending', 'active'])
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
                ->with('error', 'Không thể xóa tài khoản đang có hợp đồng hoặc công nợ chưa hoàn tất.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Xóa tài khoản thành công.');
    }

    private function manageableRoles()
    {
        return Role::query()
            ->whereIn('role_name', ['Admin', 'User', 'Client'])
            ->orderBy('id')
            ->get();
    }

    private function findManageableRole(int|string $roleId): Role
    {
        return Role::query()
            ->whereKey($roleId)
            ->whereIn('role_name', ['Admin', 'User', 'Client'])
            ->firstOrFail();
    }

    private function isClientRole(Role $role): bool
    {
        return in_array(strtolower($role->role_name), ['user', 'client'], true);
    }

    private function allowedStatusesFor(User $user): array
    {
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

        if ($tenant->contracts()->whereIn('status', ['pending', 'active'])->exists()) {
            return User::STATUS_ACTIVE;
        }

        $hasOutstandingInvoice = Invoice::query()
            ->whereHas('contract', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL])
            ->exists();

        return $hasOutstandingInvoice ? User::STATUS_SETTLING : User::STATUS_INACTIVE;
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
