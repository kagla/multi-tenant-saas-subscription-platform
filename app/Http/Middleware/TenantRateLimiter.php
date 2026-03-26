<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class TenantRateLimiter
{
    public function handle(Request $request, Closure $next, string $context = 'api'): Response
    {
        $tenant = tenant();
        $key = $this->resolveKey($request, $context);
        $maxAttempts = $this->resolveLimit($tenant, $context);
        $decaySeconds = $this->resolveDecay($context);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - RateLimiter::attempts($key)));

        return $response;
    }

    protected function resolveKey(Request $request, string $context): string
    {
        $tenantPart = tenant()?->id ?? 'global';
        $userPart = $request->ip();

        return "rate_limit:{$context}:{$tenantPart}:{$userPart}";
    }

    protected function resolveLimit(?object $tenant, string $context): int
    {
        return match ($context) {
            'login' => 5,
            'register' => 3,
            'api' => match ($tenant?->plan ?? 'free') {
                'enterprise' => 1000,
                'pro' => 200,
                default => 30,
            },
            default => 60,
        };
    }

    protected function resolveDecay(string $context): int
    {
        return match ($context) {
            'login' => 60,        // per minute
            'register' => 3600,   // per hour
            'api' => 60,          // per minute
            default => 60,
        };
    }
}
