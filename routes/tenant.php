<?php

/**
 * Shared tenant routes — included by both the subdomain group and the custom domain group.
 */

use App\Http\Controllers\FileController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UsageController;
use App\Http\Middleware\TrackApiUsage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tenant.dashboard', ['tenant' => tenant()->subdomain]);
});

// Auth-required routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('tenant.dashboard');
    })->name('tenant.dashboard');

    // Usage Dashboard
    Route::get('/usage', [UsageController::class, 'index'])->name('tenant.usage');
    Route::get('/usage/api-data', [UsageController::class, 'apiData'])->name('tenant.usage.api-data');

    // Files
    Route::get('/files', [FileController::class, 'index'])->name('tenant.files');
    Route::post('/files', [FileController::class, 'upload'])->name('tenant.files.upload');
    Route::delete('/files/{filename}', [FileController::class, 'destroy'])->name('tenant.files.destroy');

    // Settings (Owner only)
    Route::get('/settings', [TenantController::class, 'settings'])->name('tenant.settings');
    Route::put('/settings', [TenantController::class, 'updateSettings'])->name('tenant.settings.update');

    // Branding (Owner only)
    Route::get('/branding', [\App\Http\Controllers\BrandingController::class, 'edit'])->name('tenant.branding');
    Route::put('/branding', [\App\Http\Controllers\BrandingController::class, 'update'])->name('tenant.branding.update');
    Route::delete('/branding/logo', [\App\Http\Controllers\BrandingController::class, 'removeLogo'])->name('tenant.branding.removeLogo');

    // Members
    Route::get('/members', [MemberController::class, 'index'])->name('tenant.members');
    Route::patch('/members/{user}/role', [MemberController::class, 'updateRole'])->name('tenant.members.updateRole');
    Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('tenant.members.destroy');

    // Invitations (Admin/Owner only)
    Route::get('/invitations/create', [InvitationController::class, 'create'])->name('tenant.invitations.create');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('tenant.invitations.store');

    // Subscription & Billing
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('tenant.subscription.index');
    Route::get('/subscription/plans', [SubscriptionController::class, 'plans'])->name('tenant.subscription.plans');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('tenant.subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('tenant.subscription.success');
    Route::delete('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('tenant.subscription.cancel');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])->name('tenant.subscription.resume');
    Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('tenant.subscription.upgrade');

    // API routes with usage tracking (Pro+ only)
    Route::middleware(['subscription:pro,enterprise', TrackApiUsage::class])
        ->prefix('api/v1')
        ->group(function () {
            Route::get('/status', function () {
                return response()->json([
                    'status' => 'ok',
                    'tenant' => tenant()->name,
                    'plan' => tenant()->plan,
                    'timestamp' => now()->toIso8601String(),
                ]);
            })->name('tenant.api.status');

            Route::get('/usage', function () {
                $tracker = \App\Services\UsageTracker::for(tenant());
                return response()->json([
                    'api_calls_today' => $tracker->getTodayUsage('api_calls_per_day'),
                    'api_limit' => tenant()->getPlanLimit('api_calls_per_day'),
                    'storage_used_mb' => $tracker->getTotalUsage('storage_mb'),
                    'storage_limit_mb' => tenant()->getPlanLimit('storage_mb'),
                ]);
            })->name('tenant.api.usage');
        });

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
