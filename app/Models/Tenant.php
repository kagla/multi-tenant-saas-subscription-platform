<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stripe\StripeClient;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'domain',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
        'logo_path',
        'primary_color',
        'secondary_color',
        'custom_domain',
        'email_from_name',
        'email_from_address',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    const PLAN_FREE = 'free';
    const PLAN_PRO = 'pro';
    const PLAN_ENTERPRISE = 'enterprise';

    // ─── Relationships ───

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->latest();
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    // ─── Plan & Feature Helpers ───

    public function planConfig(): array
    {
        return config("plans.{$this->plan}", config('plans.free'));
    }

    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->planConfig()['features'] ?? []);
    }

    public function canUseFeature(string $metric): bool
    {
        $limit = $this->getPlanLimit($metric);

        if ($limit === PHP_INT_MAX) {
            return true;
        }

        $currentUsage = $this->usageRecords()
            ->withoutGlobalScopes()
            ->where('metric', $metric)
            ->where('recorded_at', '>=', now()->startOfDay())
            ->sum('value');

        return $currentUsage < $limit;
    }

    public function getPlanLimit(string $metric): int
    {
        return $this->planConfig()['limits'][$metric] ?? 0;
    }

    // ─── Subscription Helpers ───

    public function subscribed(): bool
    {
        if ($this->plan === self::PLAN_FREE) {
            return true;
        }

        return $this->activeSubscription()->exists();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    public function subscribe(string $plan): ?Subscription
    {
        $planConfig = config("plans.{$plan}");
        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            return null;
        }

        $stripe = $this->stripeClient();

        $stripeSubscription = $stripe->subscriptions->create([
            'customer' => $this->getOrCreateStripeCustomer(),
            'items' => [['price' => $planConfig['stripe_price_id']]],
            'trial_period_days' => $planConfig['trial_days'] ?? 0,
            'metadata' => ['tenant_id' => $this->id],
        ]);

        $this->update([
            'plan' => $plan,
            'trial_ends_at' => $stripeSubscription->trial_end
                ? now()->setTimestamp($stripeSubscription->trial_end)
                : null,
        ]);

        return Subscription::updateOrCreate(
            ['tenant_id' => $this->id, 'stripe_id' => $stripeSubscription->id],
            [
                'stripe_status' => $stripeSubscription->status,
                'stripe_plan' => $planConfig['stripe_price_id'],
                'quantity' => 1,
                'trial_ends_at' => $stripeSubscription->trial_end
                    ? now()->setTimestamp($stripeSubscription->trial_end)
                    : null,
            ]
        );
    }

    public function cancelSubscription(): bool
    {
        $subscription = $this->activeSubscription;
        if (! $subscription || ! $subscription->stripe_id) {
            return false;
        }

        $stripe = $this->stripeClient();
        $stripeSubscription = $stripe->subscriptions->update($subscription->stripe_id, [
            'cancel_at_period_end' => true,
        ]);

        $subscription->update([
            'stripe_status' => $stripeSubscription->status,
            'ends_at' => now()->setTimestamp($stripeSubscription->current_period_end),
        ]);

        $this->update([
            'subscription_ends_at' => now()->setTimestamp($stripeSubscription->current_period_end),
        ]);

        return true;
    }

    public function resumeSubscription(): bool
    {
        $subscription = $this->subscriptions()
            ->where('stripe_status', 'active')
            ->whereNotNull('ends_at')
            ->latest()
            ->first();

        if (! $subscription || ! $subscription->stripe_id) {
            return false;
        }

        $stripe = $this->stripeClient();
        $stripeSubscription = $stripe->subscriptions->update($subscription->stripe_id, [
            'cancel_at_period_end' => false,
        ]);

        $subscription->update([
            'stripe_status' => $stripeSubscription->status,
            'ends_at' => null,
        ]);

        $this->update(['subscription_ends_at' => null]);

        return true;
    }

    public function upgradeSubscription(string $newPlan): ?Subscription
    {
        $subscription = $this->activeSubscription;
        if (! $subscription || ! $subscription->stripe_id) {
            return null;
        }

        $planConfig = config("plans.{$newPlan}");
        if (! $planConfig || ! $planConfig['stripe_price_id']) {
            return null;
        }

        $stripe = $this->stripeClient();

        $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);
        $stripe->subscriptions->update($subscription->stripe_id, [
            'items' => [[
                'id' => $stripeSubscription->items->data[0]->id,
                'price' => $planConfig['stripe_price_id'],
            ]],
            'proration_behavior' => 'create_prorations',
        ]);

        $this->update(['plan' => $newPlan]);
        $subscription->update(['stripe_plan' => $planConfig['stripe_price_id']]);

        return $subscription->fresh();
    }

    // ─── Stripe Helpers ───

    public function getOrCreateStripeCustomer(): string
    {
        $subscription = $this->subscriptions()->whereNotNull('stripe_id')->latest()->first();

        if ($subscription) {
            $stripe = $this->stripeClient();
            try {
                $stripeSub = $stripe->subscriptions->retrieve($subscription->stripe_id);
                return $stripeSub->customer;
            } catch (\Exception $e) {
                // Customer not found, create new one
            }
        }

        $stripe = $this->stripeClient();
        $customer = $stripe->customers->create([
            'name' => $this->name,
            'email' => $this->users()->withoutGlobalScopes()->where('role', 'owner')->first()?->email,
            'metadata' => ['tenant_id' => $this->id],
        ]);

        return $customer->id;
    }

    protected function stripeClient(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }
}
