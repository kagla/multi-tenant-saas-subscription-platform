<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'admin@app.test',
            'password' => bcrypt('password'),
            'tenant_id' => null,
            'role' => 'owner',
            'is_super_admin' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Corp',
            'subdomain' => 'testcorp',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Tenant Owner',
            'email' => 'owner@testcorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);
    }

    public function test_admin_dashboard_accessible(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Total Tenants');
    }

    public function test_non_admin_blocked(): void
    {
        $regularUser = User::withoutGlobalScopes()->create([
            'name' => 'Regular',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'member',
        ]);

        $this->actingAs($regularUser);

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_tenants(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/tenants');

        $response->assertStatus(200);
        $response->assertSee('Test Corp');
    }

    public function test_admin_can_suspend_tenant(): void
    {
        $this->actingAs($this->admin);

        $response = $this->patch("/admin/tenants/{$this->tenant->id}/suspend");

        $response->assertRedirect();
        $this->assertFalse($this->tenant->fresh()->is_active);
    }

    public function test_admin_can_activate_tenant(): void
    {
        $this->tenant->update(['is_active' => false]);

        $this->actingAs($this->admin);

        $response = $this->patch("/admin/tenants/{$this->tenant->id}/activate");

        $response->assertRedirect();
        $this->assertTrue($this->tenant->fresh()->is_active);
    }

    public function test_admin_can_view_tenant_detail(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get("/admin/tenants/{$this->tenant->id}");

        $response->assertStatus(200);
        $response->assertSee('Test Corp');
        $response->assertSee('Tenant Owner');
    }

    public function test_admin_can_view_revenue(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/revenue');

        $response->assertStatus(200);
        $response->assertSee('MRR');
    }

    public function test_admin_can_search_tenants(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/tenants?search=testcorp');

        $response->assertStatus(200);
        $response->assertSee('Test Corp');
    }
}
