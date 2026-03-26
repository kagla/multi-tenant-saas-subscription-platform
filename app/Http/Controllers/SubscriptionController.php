<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();
        $plans = config('plans');
        $subscription = $tenant->activeSubscription;

        $usage = [
            'api_calls' => (int) $tenant->usageRecords()
                ->withoutGlobalScopes()
                ->where('metric', 'api_calls')
                ->where('recorded_at', '>=', now()->startOfDay())
                ->sum('value'),
            'storage_mb' => (float) $tenant->usageRecords()
                ->withoutGlobalScopes()
                ->where('metric', 'storage_mb')
                ->sum('value'),
            'members' => $tenant->users()->withoutGlobalScopes()->count(),
        ];

        return view('tenant.subscription.index', compact('tenant', 'plans', 'subscription', 'usage'));
    }

    public function plans(): View
    {
        $tenant = tenant();
        $plans = config('plans');

        return view('tenant.subscription.plans', compact('tenant', 'plans'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'in:pro,enterprise'],
        ]);

        $tenant = tenant();
        $plan = $request->input('plan');
        $planConfig = config("plans.{$plan}");

        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            return back()->withErrors(['plan' => '잘못된 플랜이 선택되었습니다.']);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer_email' => auth()->user()->email,
            'line_items' => [[
                'price' => $planConfig['stripe_price_id'],
                'quantity' => 1,
            ]],
            'subscription_data' => [
                'trial_period_days' => $tenant->plan === 'free' ? ($planConfig['trial_days'] ?? 0) : 0,
                'metadata' => ['tenant_id' => $tenant->id],
            ],
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan' => $plan,
            ],
            'success_url' => route('tenant.subscription.success', [
                'tenant' => $tenant->subdomain,
            ]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('tenant.subscription.index', [
                'tenant' => $tenant->subdomain,
            ]),
        ]);

        return redirect($session->url);
    }

    public function success(Request $request): View|RedirectResponse
    {
        $tenant = tenant();
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('tenant.subscription.index', ['tenant' => $tenant->subdomain]);
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription'],
            ]);

            if ($session->metadata->tenant_id != $tenant->id) {
                abort(403);
            }

            $plan = $session->metadata->plan;
            $stripeSubscription = $session->subscription;

            $tenant->update(['plan' => $plan]);

            \App\Models\Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id, 'stripe_id' => $stripeSubscription->id],
                [
                    'stripe_status' => $stripeSubscription->status,
                    'stripe_plan' => config("plans.{$plan}.stripe_price_id"),
                    'quantity' => 1,
                    'trial_ends_at' => $stripeSubscription->trial_end
                        ? now()->setTimestamp($stripeSubscription->trial_end)
                        : null,
                ]
            );

            return view('tenant.subscription.success', compact('tenant', 'plan'));
        } catch (\Exception $e) {
            return redirect()->route('tenant.subscription.index', ['tenant' => $tenant->subdomain])
                ->withErrors(['checkout' => '결제 세션 확인에 실패했습니다.']);
        }
    }

    public function cancel(): RedirectResponse
    {
        $tenant = tenant();

        if ($tenant->cancelSubscription()) {
            return back()->with('status', 'subscription-cancelled');
        }

        return back()->withErrors(['subscription' => '구독을 취소할 수 없습니다.']);
    }

    public function resume(): RedirectResponse
    {
        $tenant = tenant();

        if ($tenant->resumeSubscription()) {
            return back()->with('status', 'subscription-resumed');
        }

        return back()->withErrors(['subscription' => '구독을 재개할 수 없습니다.']);
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $request->validate([
            'plan' => ['required', 'in:pro,enterprise'],
        ]);

        $tenant = tenant();
        $newPlan = $request->input('plan');

        if ($tenant->activeSubscription) {
            $result = $tenant->upgradeSubscription($newPlan);
            if ($result) {
                return back()->with('status', 'subscription-upgraded');
            }
            return back()->withErrors(['subscription' => '구독을 업그레이드할 수 없습니다.']);
        }

        // No active subscription — redirect to checkout
        return $this->checkout($request);
    }
}
