<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalUsers = User::withoutGlobalScopes()->count();

        // Plan distribution
        $planDistribution = Tenant::select('plan', DB::raw('count(*) as count'))
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->toArray();

        // Active subscriptions
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->orWhere('stripe_status', 'trialing')
            ->count();

        // MRR calculation
        $mrr = 0;
        foreach ($planDistribution as $plan => $count) {
            $price = config("plans.{$plan}.price", 0);
            $mrr += $price * $count;
        }

        // New tenants (last 30 days)
        $newTenantsDaily = Tenant::where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw("DATE(created_at) as date"), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $signupChart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $signupChart[$date] = $newTenantsDaily[$date] ?? 0;
        }

        // DAU / MAU
        $dau = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subDay()->timestamp)
            ->distinct('user_id')
            ->count('user_id');

        $mau = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subDays(30)->timestamp)
            ->distinct('user_id')
            ->count('user_id');

        // Recent audit logs
        $recentLogs = AuditLog::with(['user', 'tenant'])
            ->latest('created_at')
            ->limit(15)
            ->get();

        return view('admin.dashboard', compact(
            'totalTenants', 'activeTenants', 'totalUsers',
            'planDistribution', 'activeSubscriptions', 'mrr',
            'signupChart', 'dau', 'mau', 'recentLogs'
        ));
    }
}
