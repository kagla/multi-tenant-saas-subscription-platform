<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UsageTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->input('plan')) {
            $query->where('plan', $plan);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'suspended') {
            $query->where('is_active', false);
        }

        $tenants = $query->withCount(['users' => function ($q) {
            $q->withoutGlobalScopes();
        }])->latest()->paginate(20)->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant): View
    {
        $members = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->get();

        $subscriptions = $tenant->subscriptions()->latest()->get();
        $tracker = UsageTracker::for($tenant);

        $usage = [
            'api_today' => $tracker->getTodayUsage('api_calls_per_day'),
            'api_limit' => $tenant->getPlanLimit('api_calls_per_day'),
            'storage' => $tracker->getTotalUsage('storage_mb'),
            'storage_limit' => $tenant->getPlanLimit('storage_mb'),
        ];

        $logs = AuditLog::where('tenant_id', $tenant->id)
            ->with('user')
            ->latest('created_at')
            ->limit(20)
            ->get();

        return view('admin.tenants.show', compact('tenant', 'members', 'subscriptions', 'usage', 'logs'));
    }

    public function impersonate(Tenant $tenant): RedirectResponse
    {
        $owner = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'owner')
            ->first();

        if (! $owner) {
            return back()->withErrors(['impersonate' => 'No owner found for this tenant.']);
        }

        session()->put('impersonating_from', auth()->id());
        auth()->login($owner);

        AuditLog::record('admin.impersonate', "Impersonating tenant '{$tenant->name}' as '{$owner->email}'", $tenant->id);

        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443]) ? '' : ':' . $port;

        return redirect("{$scheme}://{$tenant->subdomain}." . config('app.base_domain') . "{$portSuffix}/dashboard");
    }

    public function stopImpersonating(): RedirectResponse
    {
        $originalUserId = session()->pull('impersonating_from');

        if (! $originalUserId) {
            return redirect('/');
        }

        $admin = User::withoutGlobalScopes()->find($originalUserId);
        if ($admin && $admin->is_super_admin) {
            auth()->login($admin);
            return redirect('/admin');
        }

        return redirect('/');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => false]);

        AuditLog::record('admin.tenant.suspended', "Tenant '{$tenant->name}' suspended", $tenant->id);

        return back()->with('status', 'tenant-suspended');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => true]);

        AuditLog::record('admin.tenant.activated', "Tenant '{$tenant->name}' activated", $tenant->id);

        return back()->with('status', 'tenant-activated');
    }
}
