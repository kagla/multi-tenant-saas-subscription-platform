<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
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

    public function test_tenant_login_page_loads(): void
    {
        $response = $this->get("http://testcorp." . config('app.base_domain') . "/login");

        $response->assertStatus(200);
    }

    public function test_user_can_register_on_tenant(): void
    {
        app()->instance('current_tenant', $this->tenant);

        $response = $this->post("http://testcorp." . config('app.base_domain') . "/register", [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();

        $user = User::withoutGlobalScopes()->where('email', 'newuser@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->tenant->id, $user->tenant_id);
        $this->assertEquals('member', $user->role);
    }

    public function test_owner_can_login(): void
    {
        User::withoutGlobalScopes()->create([
            'name' => 'Owner',
            'email' => 'owner@testcorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->post("http://testcorp." . config('app.base_domain') . "/login", [
            'email' => 'owner@testcorp.test',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_inactive_tenant_returns_403(): void
    {
        $this->tenant->update(['is_active' => false]);

        $response = $this->get("http://testcorp." . config('app.base_domain') . "/login");

        $response->assertStatus(403);
    }

    public function test_nonexistent_tenant_returns_404(): void
    {
        $response = $this->get("http://nonexistent." . config('app.base_domain') . "/login");

        $response->assertStatus(404);
    }
}
