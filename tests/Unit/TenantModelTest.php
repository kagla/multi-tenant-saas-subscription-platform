<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantModelTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Corp',
            'subdomain' => 'testcorp',
            'plan' => 'free',
            'is_active' => true,
        ]);
    }

    public function test_is_on_plan(): void
    {
        $this->assertTrue($this->tenant->isOnPlan('free'));
        $this->assertFalse($this->tenant->isOnPlan('pro'));
    }

    public function test_has_feature_free_plan(): void
    {
        $this->assertTrue($this->tenant->hasFeature('basic_dashboard'));
        $this->assertFalse($this->tenant->hasFeature('advanced_analytics'));
        $this->assertFalse($this->tenant->hasFeature('sso'));
    }

    public function test_has_feature_pro_plan(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        $this->assertTrue($this->tenant->hasFeature('advanced_analytics'));
        $this->assertTrue($this->tenant->hasFeature('api_access'));
        $this->assertFalse($this->tenant->hasFeature('sso'));
    }

    public function test_has_feature_enterprise_plan(): void
    {
        $this->tenant->update(['plan' => 'enterprise']);

        $this->assertTrue($this->tenant->hasFeature('sso'));
        $this->assertTrue($this->tenant->hasFeature('audit_log'));
        $this->assertTrue($this->tenant->hasFeature('dedicated_support'));
    }

    public function test_plan_limits(): void
    {
        $this->assertEquals(100, $this->tenant->getPlanLimit('api_calls_per_day'));
        $this->assertEquals(100, $this->tenant->getPlanLimit('storage_mb'));
        $this->assertEquals(3, $this->tenant->getPlanLimit('members'));
    }

    public function test_pro_plan_limits(): void
    {
        $this->tenant->update(['plan' => 'pro']);

        $this->assertEquals(10000, $this->tenant->getPlanLimit('api_calls_per_day'));
        $this->assertEquals(10240, $this->tenant->getPlanLimit('storage_mb'));
        $this->assertEquals(20, $this->tenant->getPlanLimit('members'));
    }

    public function test_enterprise_unlimited(): void
    {
        $this->tenant->update(['plan' => 'enterprise']);

        $this->assertEquals(PHP_INT_MAX, $this->tenant->getPlanLimit('api_calls_per_day'));
        $this->assertEquals(PHP_INT_MAX, $this->tenant->getPlanLimit('storage_mb'));
        $this->assertEquals(PHP_INT_MAX, $this->tenant->getPlanLimit('members'));
    }

    public function test_subscribed_free_plan(): void
    {
        $this->assertTrue($this->tenant->subscribed());
    }

    public function test_on_trial(): void
    {
        $this->assertFalse($this->tenant->onTrial());

        $this->tenant->update(['trial_ends_at' => now()->addDays(7)]);
        $this->assertTrue($this->tenant->onTrial());

        $this->tenant->update(['trial_ends_at' => now()->subDay()]);
        $this->assertFalse($this->tenant->onTrial());
    }

    public function test_on_grace_period(): void
    {
        $this->assertFalse($this->tenant->onGracePeriod());

        $this->tenant->update(['subscription_ends_at' => now()->addDays(3)]);
        $this->assertTrue($this->tenant->onGracePeriod());
    }
}
