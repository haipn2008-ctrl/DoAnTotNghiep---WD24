<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected function adminOnly(): void
    {
        if (! Auth::check() || Auth::user()->role_id !== 1) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->adminOnly();

        $search = $request->query('search');

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

        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Tạo tài khoản thành công.');
    }

    public function edit(User $user)
    {
        $this->adminOnly();

        $roles = Role::all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(User $user)
    {
        $this->adminOnly();

        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Không thể xóa chính bạn.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Xóa tài khoản thành công.');
    }
}
