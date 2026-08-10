<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->status === User::STATUS_PENDING) {
            return redirect()->route('account.activation.show');
        }

        if (! $user?->canAccessPortal()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản đã bị khóa hoặc ngừng hoạt động. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        return $next($request);
    }
}
