<?php

namespace App\Http\Controllers;

use App\Mail\TenantInvitation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function create(): View
    {
        $tenant = tenant();
        Gate::authorize('manageMembers', $tenant);

        $invitations = Invitation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();

        return view('tenant.invitations.create', compact('tenant', 'invitations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = tenant();
        Gate::authorize('manageMembers', $tenant);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,member'],
        ]);

        // Check if user is already a member
        $existing = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['email' => '이미 이 조직의 멤버입니다.']);
        }

        // Check for existing pending invitation
        $pendingInvite = Invitation::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvite) {
            return back()->withErrors(['email' => '이미 초대가 발송되었습니다.']);
        }

        $invitation = Invitation::create([
            'tenant_id' => $tenant->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        Mail::to($validated['email'])->queue(new TenantInvitation($invitation, $tenant));

        return back()->with('status', 'invitation-sent');
    }

    public function accept(string $token): View|RedirectResponse
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('tenant.invitations.accept', compact('invitation'));
    }

    public function processAccept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        // Check if user already has an account
        $user = User::withoutGlobalScopes()->where('email', $invitation->email)->first();

        if ($user) {
            // Link existing user to tenant
            if ($user->tenant_id && $user->tenant_id !== $invitation->tenant_id) {
                return back()->withErrors(['email' => '이 계정은 이미 다른 조직에 소속되어 있습니다.']);
            }
            $user->withoutGlobalScopes()->where('id', $user->id)->update([
                'tenant_id' => $invitation->tenant_id,
                'role' => $invitation->role,
            ]);
        } else {
            // Create new user
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::withoutGlobalScopes()->create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'tenant_id' => $invitation->tenant_id,
                'role' => $invitation->role,
            ]);
        }

        $invitation->withoutGlobalScopes()
            ->where('id', $invitation->id)
            ->update(['accepted_at' => now()]);

        auth()->login($user);

        $tenant = $invitation->tenant;

        return redirect(
            request()->getScheme() . '://' . $tenant->subdomain . '.' . config('app.base_domain') . '/dashboard'
        )->with('status', 'invitation-accepted');
    }
}
