<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'required|string|max:20',
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->update($data);
            $user->tenant?->update([
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
        });

        return back()->with('success', 'Đã cập nhật thông tin tài khoản.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Đã đổi mật khẩu thành công.');
    }
}
