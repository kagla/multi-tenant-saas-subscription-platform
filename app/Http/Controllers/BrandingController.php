<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function edit(): View
    {
        $tenant = tenant();
        Gate::authorize('update', $tenant);

        return view('tenant.branding.edit', compact('tenant'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('update', $tenant);

        $validated = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+[a-zA-Z0-9]$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $path = $request->file('logo')->store("tenants/{$tenant->id}/branding", 'public');
            $tenant->logo_path = $path;
        }

        $tenant->primary_color = $validated['primary_color'];
        $tenant->secondary_color = $validated['secondary_color'];
        $tenant->email_from_name = $validated['email_from_name'];
        $tenant->email_from_address = $validated['email_from_address'];
        $tenant->custom_domain = $validated['custom_domain'];
        $tenant->save();

        return back()->with('status', 'branding-updated');
    }

    public function removeLogo(): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('update', $tenant);

        if ($tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        return back()->with('status', 'logo-removed');
    }
}
