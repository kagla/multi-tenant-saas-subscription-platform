<?php

namespace App\Http\Middleware;

use App\Services\UsageTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        $tracker = UsageTracker::for($tenant);

        if (! $tracker->canUse('api_calls_per_day')) {
            return response()->json([
                'error' => '요청 한도 초과',
                'message' => '일일 API 호출 한도를 초과했습니다. 플랜을 업그레이드해 주세요.',
                'limit' => $tenant->getPlanLimit('api_calls_per_day'),
                'used' => (int) $tracker->getTodayUsage('api_calls_per_day'),
                'plan' => $tenant->plan,
                'upgrade_url' => route('tenant.subscription.plans', ['tenant' => $tenant->subdomain]),
            ], 429);
        }

        $response = $next($request);

        // Track usage after successful response
        $tracker->track('api_calls_per_day');

        // Add rate limit headers
        $limit = $tenant->getPlanLimit('api_calls_per_day');
        $remaining = max(0, (int) $tracker->getRemainingQuota('api_calls_per_day'));

        $response->headers->set('X-RateLimit-Limit', $limit === PHP_INT_MAX ? 'unlimited' : $limit);
        $response->headers->set('X-RateLimit-Remaining', $limit === PHP_INT_MAX ? 'unlimited' : $remaining);

        return $response;
    }
}
