<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Tenant::observe(TenantObserver::class);
        User::observe(UserObserver::class);
    }
}
