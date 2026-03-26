<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function settings(): View
    {
        $tenant = tenant();
        Gate::authorize('update', $tenant);

        return view('tenant.settings', compact('tenant'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('update', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:63', 'alpha_dash', 'unique:tenants,subdomain,' . $tenant->id],
        ]);

        $tenant->update($validated);

        return back()->with('status', 'settings-updated');
    }
}
