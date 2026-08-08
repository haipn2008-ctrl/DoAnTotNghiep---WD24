<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $allowedRoleNames = match (strtolower($role)) {
            'admin' => ['admin'],
            'client', 'user' => ['client', 'user'],
            default => null,
        };

        abort_if($allowedRoleNames === null, 500, 'Cấu hình vai trò không hợp lệ.');

        $actualRoleName = strtolower((string) $request->user()?->role?->role_name);
        abort_unless(in_array($actualRoleName, $allowedRoleNames, true), 403);

        return $next($request);
    }
}
