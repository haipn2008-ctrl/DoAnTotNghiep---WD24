<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $email = Str::lower($credentials['email']);
        $reset = DB::transaction(function () use ($credentials, $email): bool {
            $otp = DB::table('password_reset_otps')->where('email', $email)->lockForUpdate()->first();
            if (! $otp || now()->greaterThan($otp->expires_at) || $otp->attempts >= 5) {
                return false;
            }
            if (! Hash::check($credentials['code'], $otp->code)) {
                DB::table('password_reset_otps')->where('email', $email)->increment('attempts');

                return false;
            }
            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            if (! $user) {
                return false;
            }
            $user->forceFill([
                'password' => $credentials['password'],
                'remember_token' => Str::random(60),
            ])->save();
            DB::table('password_reset_otps')->where('email', $email)->delete();

            return true;
        });

        if ($reset) {
            return redirect()->route('login')->with('status', 'Mật khẩu đã được đặt lại. Bạn có thể đăng nhập bằng mật khẩu mới.');
        }

        return back()->withErrors(['code' => 'Mã xác thực không đúng, đã hết hạn hoặc đã dùng quá số lần cho phép.'])
            ->withInput($request->only('email'));

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with(
                'status',
                'Mật khẩu đã được đặt lại. Bạn có thể đăng nhập bằng mật khẩu mới.'
            );
        }

        return back()
            ->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'])
            ->withInput($request->only('email'));
    }
}
