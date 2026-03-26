<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantCache
{
    public static function get(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public static function getBySubdomain(string $subdomain): ?Tenant
    {
        $id = Cache::remember("tenant.subdomain.{$subdomain}", 3600, function () use ($subdomain) {
            return Tenant::where('subdomain', $subdomain)->value('id');
        });

        return $id ? Tenant::find($id) : null;
    }

    public static function getByCustomDomain(string $domain): ?Tenant
    {
        $id = Cache::remember("tenant.domain.{$domain}", 3600, function () use ($domain) {
            return Tenant::where('custom_domain', $domain)->value('id');
        });

        return $id ? Tenant::find($id) : null;
    }

    public static function forget(Tenant $tenant): void
    {
        Cache::forget("tenant.subdomain.{$tenant->subdomain}");

        if ($tenant->custom_domain) {
            Cache::forget("tenant.domain.{$tenant->custom_domain}");
        }
    }

    public static function plans(): array
    {
        return Cache::remember('plans', 86400, function () {
            return config('plans');
        });
    }
}
