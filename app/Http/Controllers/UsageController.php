<?php

namespace App\Http\Controllers;

use App\Services\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class UsageController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();
        $tracker = UsageTracker::for($tenant);

        $metrics = [
            'api_calls_per_day' => [
                'label' => 'API Calls (today)',
                'current' => $tracker->getTodayUsage('api_calls_per_day'),
                'limit' => $tenant->getPlanLimit('api_calls_per_day'),
                'percent' => $tracker->getUsagePercent('api_calls_per_day'),
                'remaining' => $tracker->getRemainingQuota('api_calls_per_day'),
            ],
            'storage_mb' => [
                'label' => 'Storage',
                'current' => $tracker->getTotalUsage('storage_mb'),
                'limit' => $tenant->getPlanLimit('storage_mb'),
                'percent' => $tracker->getUsagePercent('storage_mb'),
                'remaining' => $tracker->getRemainingQuota('storage_mb'),
                'unit' => 'MB',
            ],
            'members' => [
                'label' => 'Team Members',
                'current' => $tenant->users()->withoutGlobalScopes()->count(),
                'limit' => $tenant->getPlanLimit('members'),
                'percent' => $tenant->getPlanLimit('members') === PHP_INT_MAX ? 0 :
                    round($tenant->users()->withoutGlobalScopes()->count() / $tenant->getPlanLimit('members') * 100, 1),
                'remaining' => $tenant->getPlanLimit('members') === PHP_INT_MAX ? PHP_INT_MAX :
                    max(0, $tenant->getPlanLimit('members') - $tenant->users()->withoutGlobalScopes()->count()),
            ],
        ];

        $apiDaily = $tracker->getDailyUsageForPeriod('api_calls_per_day', 30);

        return view('tenant.usage.index', compact('tenant', 'metrics', 'apiDaily'));
    }

    public function apiData(): JsonResponse
    {
        $tenant = tenant();
        $tracker = UsageTracker::for($tenant);

        return response()->json([
            'api_calls' => [
                'today' => $tracker->getTodayUsage('api_calls_per_day'),
                'limit' => $tenant->getPlanLimit('api_calls_per_day'),
                'percent' => $tracker->getUsagePercent('api_calls_per_day'),
                'remaining' => $tracker->getRemainingQuota('api_calls_per_day'),
            ],
            'storage' => [
                'used' => $tracker->getTotalUsage('storage_mb'),
                'limit' => $tenant->getPlanLimit('storage_mb'),
                'percent' => $tracker->getUsagePercent('storage_mb'),
            ],
            'daily_chart' => $tracker->getDailyUsageForPeriod('api_calls_per_day', 30),
        ]);
    }
}
