<?php

if (! function_exists('tenant')) {
    function tenant(): ?\App\Models\Tenant
    {
        return app()->bound('current_tenant') ? app('current_tenant') : null;
    }
}

if (! function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return tenant()?->id;
    }
}

if (! function_exists('tenant_url')) {
    function tenant_url(string $path = ''): string
    {
        $tenant = tenant();
        $scheme = request()->getScheme();
        $baseDomain = config('app.base_domain', 'app.test');

        if ($tenant) {
            return $scheme . '://' . $tenant->subdomain . '.' . $baseDomain . '/' . ltrim($path, '/');
        }

        return $scheme . '://' . $baseDomain . '/' . ltrim($path, '/');
    }
}
