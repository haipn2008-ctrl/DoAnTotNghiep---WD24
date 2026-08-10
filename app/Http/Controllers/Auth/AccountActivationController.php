<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->isActive()) {
            return redirect()->route('dashboard');
        }

        abort_unless($request->user()->isClient() && $request->user()->status === User::STATUS_PENDING, 403);

        return view('auth.activate', ['user' => $request->user()]);
    }

    public function activate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isClient() && $request->user()->status === User::STATUS_PENDING, 403);

        $tenantId = $request->user()->tenant?->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'phone')->ignore($request->user()->id),
                Rule::unique('tenants', 'phone')->ignore($tenantId),
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            'accept_terms' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = User::with('tenant')->lockForUpdate()->findOrFail($request->user()->id);

            abort_unless($user->isClient() && $user->status === User::STATUS_PENDING, 403);

            if (Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Mật khẩu mới phải khác mật khẩu tạm thời.',
                ]);
            }

            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'status' => User::STATUS_ACTIVE,
                'activated_at' => now(),
                'terms_accepted_at' => now(),
                'must_change_password' => false,
            ]);

            $user->tenant?->update([
                'full_name' => $data['name'],
                'phone' => $data['phone'],
            ]);
        });

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Kích hoạt tài khoản thành công.');
    }
}
