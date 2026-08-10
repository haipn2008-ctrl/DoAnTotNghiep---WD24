<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Bạn đã đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = $request->user();

            if (! in_array($user->status, [User::STATUS_PENDING, User::STATUS_ACTIVE, User::STATUS_SETTLING], true)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Tài khoản đã bị khóa hoặc ngừng hoạt động. Vui lòng liên hệ quản trị viên.',
                ])->onlyInput('email');
            }

            if ($user->status === User::STATUS_PENDING) {
                if (! $user->isClient()) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return back()->withErrors(['email' => 'Tài khoản chờ kích hoạt không thuộc vai trò khách thuê.']);
                }
            }

            $user->forceFill(['last_login_at' => now()])->save();

            // Never reuse an intended URL from another role's expired session.
            $request->session()->forget('url.intended');

            return $user->status === User::STATUS_PENDING
                ? redirect()->route('account.activation.show')
                : redirect()->route('dashboard');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.home');
        }

        if ($user->isClient()) {
            return redirect()->route('client.home');
        }

        abort(403, 'Tài khoản không có vai trò hợp lệ.');
    }
}
