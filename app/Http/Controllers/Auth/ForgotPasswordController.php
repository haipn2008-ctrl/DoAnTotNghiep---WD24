<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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
