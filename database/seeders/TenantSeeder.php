<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (no tenant)
        User::withoutGlobalScopes()->create([
            'name' => 'Super Admin',
            'email' => 'admin@app.test',
            'password' => Hash::make('password'),
            'tenant_id' => null,
            'role' => 'owner',
            'is_super_admin' => true,
        ]);

        // Acme Corp (Free plan)
        $acme = Tenant::create([
            'name' => 'Acme Corp',
            'subdomain' => 'acme',
            'plan' => 'free',
            'is_active' => true,
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'Acme Owner',
            'email' => 'owner@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $acme->id,
            'role' => 'owner',
        ]);

        // TechCorp (Pro plan)
        $techcorp = Tenant::create([
            'name' => 'TechCorp',
            'subdomain' => 'techcorp',
            'plan' => 'pro',
            'is_active' => true,
        ]);

        User::withoutGlobalScopes()->create([
            'name' => 'TechCorp Owner',
            'email' => 'owner@techcorp.test',
            'password' => Hash::make('password'),
            'tenant_id' => $techcorp->id,
            'role' => 'owner',
        ]);
    }
}
