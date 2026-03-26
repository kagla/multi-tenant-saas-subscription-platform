<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use App\Services\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageTrackerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private UsageTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Corp',
            'subdomain' => 'testcorp',
            'plan' => 'free',
            'is_active' => true,
        ]);

        // Create owner for notifications
        User::withoutGlobalScopes()->create([
            'name' => 'Owner',
            'email' => 'owner@testcorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);

        $this->tracker = UsageTracker::for($this->tenant);
    }

    public function test_track_records_usage(): void
    {
        $this->tracker->track('api_calls_per_day');

        $this->assertEquals(1, $this->tracker->getTodayUsage('api_calls_per_day'));
    }

    public function test_track_custom_value(): void
    {
        $this->tracker->track('storage_mb', 5.5);

        $this->assertEquals(5.5, $this->tracker->getTotalUsage('storage_mb'));
    }

    public function test_can_use_within_limit(): void
    {
        $this->assertTrue($this->tracker->canUse('api_calls_per_day'));

        // Free plan limit is 100
        for ($i = 0; $i < 100; $i++) {
            $this->tracker->track('api_calls_per_day');
        }

        $this->assertFalse($this->tracker->canUse('api_calls_per_day'));
    }

    public function test_remaining_quota(): void
    {
        $this->assertEquals(100, $this->tracker->getRemainingQuota('api_calls_per_day'));

        $this->tracker->track('api_calls_per_day', 30);

        $this->assertEquals(70, $this->tracker->getRemainingQuota('api_calls_per_day'));
    }

    public function test_usage_percent(): void
    {
        $this->assertEquals(0, $this->tracker->getUsagePercent('api_calls_per_day'));

        $this->tracker->track('api_calls_per_day', 50);

        $this->assertEquals(50, $this->tracker->getUsagePercent('api_calls_per_day'));
    }

    public function test_enterprise_unlimited(): void
    {
        $this->tenant->update(['plan' => 'enterprise']);

        $this->tracker->track('api_calls_per_day', 999999);

        $this->assertTrue($this->tracker->canUse('api_calls_per_day'));
        $this->assertEquals(PHP_INT_MAX, $this->tracker->getRemainingQuota('api_calls_per_day'));
        $this->assertEquals(0, $this->tracker->getUsagePercent('api_calls_per_day'));
    }

    public function test_daily_usage_chart(): void
    {
        $this->tracker->track('api_calls_per_day', 10);

        $chart = $this->tracker->getDailyUsageForPeriod('api_calls_per_day', 7);

        $this->assertCount(7, $chart);
        $this->assertEquals(10, $chart[now()->format('Y-m-d')]);
    }

    public function test_monthly_usage(): void
    {
        $this->tracker->track('api_calls_per_day', 25);

        $this->assertEquals(25, $this->tracker->getMonthlyUsage('api_calls_per_day'));
    }
}
