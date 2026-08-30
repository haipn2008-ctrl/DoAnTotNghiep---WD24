<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', Str::lower($credentials['email']))->first();
        if ($user) {
            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_otps')->updateOrInsert(['email' => $user->email], [
                'code' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
                'created_at' => now(),
            ]);
            $user->notify(new ResetPasswordNotification($code));
        }

        return redirect()->route('password.reset', ['email' => $credentials['email']])->with(
            'status',
            'Nếu email đã được đăng ký, mã xác thực 6 số đã được gửi đến hộp thư của bạn.'
        );

        $status = Password::sendResetLink($credentials);

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withErrors(['email' => 'Bạn vừa yêu cầu gửi email. Vui lòng chờ trước khi thử lại.'])
                ->onlyInput('email');
        }

        // Use the same response for registered and unknown addresses to avoid
        // exposing which email addresses have an account in the system.
        return back()->with(
            'status',
            'Nếu email đã được đăng ký, liên kết đặt lại mật khẩu đã được gửi đến hộp thư của bạn.'
        );
    }
}
