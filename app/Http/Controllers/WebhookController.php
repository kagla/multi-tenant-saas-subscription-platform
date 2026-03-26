<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook: invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $method = 'handle' . str_replace('.', '', ucwords(str_replace('_', '.', $event->type), '.'));

        if (method_exists($this, $method)) {
            return $this->{$method}($event->data->object);
        }

        return response('Webhook received', 200);
    }

    protected function handleCheckoutSessionCompleted(object $session): Response
    {
        $tenantId = $session->metadata->tenant_id ?? null;
        $plan = $session->metadata->plan ?? null;

        if (! $tenantId || ! $plan) {
            Log::warning('Stripe webhook: checkout.session.completed missing metadata', [
                'session_id' => $session->id,
            ]);
            return response('Missing metadata', 200);
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return response('Tenant not found', 200);
        }

        $tenant->update(['plan' => $plan]);

        if ($session->subscription) {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $stripeSubscription = $stripe->subscriptions->retrieve($session->subscription);

            Subscription::updateOrCreate(
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
        }

        Log::info("Stripe webhook: tenant {$tenantId} subscribed to {$plan}");

        return response('OK', 200);
    }

    protected function handleCustomerSubscriptionUpdated(object $subscription): Response
    {
        $sub = Subscription::where('stripe_id', $subscription->id)->first();
        if (! $sub) {
            return response('Subscription not found', 200);
        }

        $sub->update([
            'stripe_status' => $subscription->status,
            'ends_at' => $subscription->cancel_at_period_end
                ? now()->setTimestamp($subscription->current_period_end)
                : null,
        ]);

        $tenant = $sub->tenant;
        if ($tenant && $subscription->cancel_at_period_end) {
            $tenant->update([
                'subscription_ends_at' => now()->setTimestamp($subscription->current_period_end),
            ]);
        } elseif ($tenant) {
            $tenant->update(['subscription_ends_at' => null]);
        }

        Log::info("Stripe webhook: subscription {$subscription->id} updated to {$subscription->status}");

        return response('OK', 200);
    }

    protected function handleCustomerSubscriptionDeleted(object $subscription): Response
    {
        $sub = Subscription::where('stripe_id', $subscription->id)->first();
        if (! $sub) {
            return response('Subscription not found', 200);
        }

        $sub->update([
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);

        $tenant = $sub->tenant;
        if ($tenant) {
            $tenant->update([
                'plan' => Tenant::PLAN_FREE,
                'subscription_ends_at' => now(),
            ]);
        }

        Log::info("Stripe webhook: subscription {$subscription->id} deleted, tenant downgraded to free");

        return response('OK', 200);
    }

    protected function handleInvoicePaymentSucceeded(object $invoice): Response
    {
        Log::info("Stripe webhook: invoice {$invoice->id} payment succeeded", [
            'customer' => $invoice->customer,
            'amount' => $invoice->amount_paid,
        ]);

        return response('OK', 200);
    }

    protected function handleInvoicePaymentFailed(object $invoice): Response
    {
        $subscriptionId = $invoice->subscription;
        $sub = Subscription::where('stripe_id', $subscriptionId)->first();

        if ($sub) {
            $sub->update(['stripe_status' => 'past_due']);

            Log::warning("Stripe webhook: invoice payment failed for subscription {$subscriptionId}", [
                'tenant_id' => $sub->tenant_id,
                'amount' => $invoice->amount_due,
            ]);
        }

        return response('OK', 200);
    }
}
