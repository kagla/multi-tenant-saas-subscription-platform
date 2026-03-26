<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next, string ...$plans): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            abort(403, '테넌트 컨텍스트가 없습니다.');
        }

        // If no specific plans required, just check that subscription is active
        if (empty($plans)) {
            if ($tenant->subscribed() || $tenant->onTrial() || $tenant->onGracePeriod()) {
                return $next($request);
            }

            return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
                ->with('status', 'subscription-required');
        }

        // Check if tenant is on one of the required plans
        if (in_array($tenant->plan, $plans)) {
            return $next($request);
        }

        return redirect()->route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])
            ->with('status', 'plan-upgrade-required');
    }
}
