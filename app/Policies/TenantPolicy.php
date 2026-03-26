<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id && $user->role === 'owner';
    }

    public function manageMembers(User $user, Tenant $tenant): bool
    {
        return $user->tenant_id === $tenant->id && in_array($user->role, ['owner', 'admin']);
    }
}
