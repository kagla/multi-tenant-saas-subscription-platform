<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = static::resolveCurrentTenantId()) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    public static function resolveCurrentTenantId(): ?int
    {
        if (app()->bound('current_tenant')) {
            return app('current_tenant')->id;
        }

        return null;
    }
}
