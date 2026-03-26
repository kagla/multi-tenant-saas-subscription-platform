<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();
        $members = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->get();

        return view('tenant.members.index', compact('tenant', 'members'));
    }

    public function updateRole(Request $request, string $tenantSubdomain, string $userId): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('manageMembers', $tenant);

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        if ($user->role === 'owner') {
            return back()->withErrors(['role' => 'Cannot change the owner\'s role.']);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,member'],
        ]);

        $user->role = $validated['role'];
        $user->saveQuietly();

        return back()->with('status', 'role-updated');
    }

    public function destroy(Request $request, string $tenantSubdomain, string $userId): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('manageMembers', $tenant);

        $user = User::withoutGlobalScopes()
            ->where('id', $userId)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['member' => 'You cannot remove yourself.']);
        }

        if ($user->role === 'owner') {
            return back()->withErrors(['member' => 'Cannot remove the owner.']);
        }

        $user->delete();

        return back()->with('status', 'member-removed');
    }
}
