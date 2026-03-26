<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Pro Corp',
            'subdomain' => 'procorp',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        $this->owner = User::withoutGlobalScopes()->create([
            'name' => 'Owner',
            'email' => 'owner@procorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);
    }

    public function test_api_returns_rate_limit_headers(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->get("http://procorp." . config('app.base_domain') . "/api/v1/status");

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_api_blocked_for_free_plan(): void
    {
        $freeTenant = Tenant::create([
            'name' => 'Free Corp',
            'subdomain' => 'freecorp',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $freeUser = User::withoutGlobalScopes()->create([
            'name' => 'Free Owner',
            'email' => 'owner@freecorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $freeTenant->id,
            'role' => 'owner',
        ]);

        $this->actingAs($freeUser);
        app()->instance('current_tenant', $freeTenant);

        $response = $this->get("http://freecorp." . config('app.base_domain') . "/api/v1/status");

        $response->assertRedirect();
    }

    public function test_api_returns_429_when_limit_exceeded(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        // Exhaust the API limit
        $tracker = UsageTracker::for($this->tenant);
        $tracker->track('api_calls_per_day', 10000);

        $response = $this->get("http://procorp." . config('app.base_domain') . "/api/v1/status");

        $response->assertStatus(429);
        $response->assertJsonStructure(['error', 'message', 'limit', 'used', 'plan']);
    }

    public function test_usage_dashboard_accessible(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->get("http://procorp." . config('app.base_domain') . "/usage");

        $response->assertStatus(200);
        $response->assertSee('Usage Dashboard');
    }
}
