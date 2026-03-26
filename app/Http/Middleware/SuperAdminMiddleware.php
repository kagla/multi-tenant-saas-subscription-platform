<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->is_super_admin) {
            abort(403, '권한이 없습니다. 슈퍼 관리자만 접근 가능합니다.');
        }

        return $next($request);
    }
}
