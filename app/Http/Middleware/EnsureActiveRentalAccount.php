<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRentalAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status !== User::STATUS_ACTIVE) {
            return redirect()->route('client.invoices.index')->with(
                'warning',
                'Hợp đồng đã kết thúc. Tài khoản hiện chỉ được dùng để quyết toán và xem lịch sử.'
            );
        }

        return $next($request);
    }
}
