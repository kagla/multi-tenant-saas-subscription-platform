<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        AuditLog::record('user.created', "User '{$user->name}' ({$user->email}) created with role '{$user->role}'", $user->tenant_id);
    }

    public function updated(User $user): void
    {
        if ($user->wasChanged('role')) {
            AuditLog::record(
                'user.role_changed',
                "User '{$user->name}' role changed from '{$user->getOriginal('role')}' to '{$user->role}'",
                $user->tenant_id
            );
        }
    }

    public function deleted(User $user): void
    {
        AuditLog::record('user.deleted', "User '{$user->name}' ({$user->email}) deleted", $user->tenant_id);
    }
}
