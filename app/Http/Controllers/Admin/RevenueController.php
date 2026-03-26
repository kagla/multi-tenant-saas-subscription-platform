<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevenueController extends Controller
{
    public function index(): View
    {
        // Plan distribution with pricing
        $planCounts = Tenant::select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        $planRevenue = [];
        $mrr = 0;
        foreach (['free', 'pro', 'enterprise'] as $plan) {
            $count = $planCounts[$plan] ?? 0;
            $price = config("plans.{$plan}.price", 0);
            $revenue = $price * $count;
            $planRevenue[$plan] = ['count' => $count, 'price' => $price, 'revenue' => $revenue];
            $mrr += $revenue;
        }

        $arr = $mrr * 12;

        // Monthly revenue trend (last 12 months based on tenant creation × plan price)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');

            $revenue = 0;
            $tenantsActive = Tenant::where('created_at', '<=', $month->endOfMonth())
                ->where('is_active', true)
                ->get();

            foreach ($tenantsActive as $t) {
                $revenue += config("plans.{$t->plan}.price", 0);
            }

            $monthlyRevenue[$monthKey] = $revenue;
        }

        // Churn: tenants that went from paid to free in last 30 days
        $churnedCount = Tenant::where('plan', 'free')
            ->where('subscription_ends_at', '>=', now()->subDays(30))
            ->count();

        $totalPaid = Tenant::where('plan', '!=', 'free')->count();
        $churnRate = ($totalPaid + $churnedCount) > 0
            ? round($churnedCount / ($totalPaid + $churnedCount) * 100, 1)
            : 0;

        // Active subscriptions
        $activeSubscriptions = Subscription::whereIn('stripe_status', ['active', 'trialing'])->count();

        return view('admin.revenue.index', compact(
            'planRevenue', 'mrr', 'arr', 'monthlyRevenue',
            'churnRate', 'churnedCount', 'activeSubscriptions'
        ));
    }
}
