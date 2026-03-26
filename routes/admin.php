<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Stop impersonating (outside super_admin middleware — only requires auth + session check)
Route::get('admin/stop-impersonating', [TenantController::class, 'stopImpersonating'])
    ->middleware('auth')
    ->name('admin.stop-impersonating');

Route::prefix('admin')->middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->name('admin.tenants');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('admin.tenants.show');
    Route::post('/tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('admin.tenants.impersonate');
    Route::patch('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('admin.tenants.suspend');
    Route::patch('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('admin.tenants.activate');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('admin.users');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');

    // Revenue
    Route::get('/revenue', [RevenueController::class, 'index'])->name('admin.revenue');
});
