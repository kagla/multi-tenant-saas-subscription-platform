<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Services\TenantCache;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        AuditLog::record('tenant.created', "Tenant '{$tenant->name}' created (subdomain: {$tenant->subdomain})", $tenant->id);
    }

    public function updated(Tenant $tenant): void
    {
        TenantCache::forget($tenant);

        $changes = $tenant->getChanges();
        unset($changes['updated_at']);
        if (empty($changes)) {
            return;
        }

        $desc = "Tenant '{$tenant->name}' updated: " . collect($changes)->map(fn($v, $k) => "{$k}={$v}")->implode(', ');
        AuditLog::record('tenant.updated', $desc, $tenant->id);
    }

    public function deleted(Tenant $tenant): void
    {
        TenantCache::forget($tenant);
        AuditLog::record('tenant.deleted', "Tenant '{$tenant->name}' deleted", $tenant->id);
    }
}
