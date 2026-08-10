<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('client.account.edit', [
            'user' => $request->user()->load('tenant'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
                Rule::unique('tenants', 'email')->ignore($tenant?->id),
            ],
            'phone' => [
                'required', 'regex:/^[0-9]{10,15}$/',
                Rule::unique('tenants', 'phone')->ignore($tenant?->id),
            ],
        ]);

        try {
            DB::transaction(function () use ($user, $data) {
                $lockedUser = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
                $tenant = $lockedUser->tenant()->lockForUpdate()->first();
                $lockedUser->update($data);
                $tenant?->update([
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ]);
            });
        } catch (QueryException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'Email hoặc số điện thoại đã được sử dụng.',
                'phone' => 'Email hoặc số điện thoại đã được sử dụng.',
            ]);
        }

        return back()->with('success', 'Đã cập nhật thông tin tài khoản.');
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
