<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Corp',
            'subdomain' => 'testcorp',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        $this->owner = User::withoutGlobalScopes()->create([
            'name' => 'Owner',
            'email' => 'owner@testcorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_view_members(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->get("http://testcorp." . config('app.base_domain') . "/members");

        $response->assertStatus(200);
        $response->assertSee('Owner');
    }

    public function test_owner_can_change_member_role(): void
    {
        $member = User::withoutGlobalScopes()->create([
            'name' => 'Member',
            'email' => 'member@testcorp.test',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'role' => 'member',
        ]);

        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->patch(
            "http://testcorp." . config('app.base_domain') . "/members/{$member->id}/role",
            ['role' => 'admin']
        );

        $response->assertRedirect();
        $this->assertEquals('admin', $member->fresh()->role);
    }

    public function test_cannot_change_owner_role(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->patch(
            "http://testcorp." . config('app.base_domain') . "/members/{$this->owner->id}/role",
            ['role' => 'member']
        );

        $response->assertRedirect();
        $this->assertEquals('owner', $this->owner->fresh()->role);
    }

    public function test_cannot_remove_self(): void
    {
        $this->actingAs($this->owner);
        app()->instance('current_tenant', $this->tenant);

        $response = $this->delete(
            "http://testcorp." . config('app.base_domain') . "/members/{$this->owner->id}"
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }
}
