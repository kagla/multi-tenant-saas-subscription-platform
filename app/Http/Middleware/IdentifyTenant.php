<?php

namespace App\Http\Middleware;

use App\Services\TenantCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = config('app.base_domain', 'app.test');

        $tenant = null;

        // 1. Try subdomain match: "acme.app.test" → "acme"
        $subdomain = str_replace('.' . $baseDomain, '', $host);
        if ($subdomain !== $host && $subdomain !== '') {
            $tenant = TenantCache::getBySubdomain($subdomain);
        }

        // 2. Try custom domain match
        if (! $tenant && $subdomain === $host) {
            $tenant = TenantCache::getByCustomDomain($host);
        }

        if (! $tenant) {
            if ($subdomain === $host || $subdomain === '') {
                return $next($request);
            }
            abort(404, '테넌트를 찾을 수 없습니다.');
        }

        if (! $tenant->is_active) {
            abort(403, '이 테넌트 계정은 비활성화되었습니다.');
        }

        app()->instance('current_tenant', $tenant);

        if ($request->hasSession()) {
            $request->session()->put('tenant_id', $tenant->id);
        }

        URL::defaults(['tenant' => $tenant->subdomain]);

        view()->share('currentTenant', $tenant);

        return $next($request);
    }
}
