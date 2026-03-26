<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

// ─── Main domain routes (app.test) ───
Route::get('/', function () {
    return view('welcome');
});

// Main domain auth (for super admin login)
Route::get('/login', function () {
    return view('admin.login');
})->middleware('guest')->name('main.login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email', 'password' => 'required']);

    if (! \Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
        return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
    }

    $request->session()->regenerate();
    return redirect('/admin');
})->middleware('guest');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

// Invitation accept (on main domain)
Route::get('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->name('invitations.accept');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'processAccept'])
    ->name('invitations.processAccept');

// Stripe Webhook (no CSRF, no tenant context)
Route::post('/webhook/stripe', [WebhookController::class, 'handleWebhook'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
    ->name('webhook.stripe');

// ─── Tenant subdomain routes ({tenant}.app.test) ───
Route::domain('{tenant}.' . config('app.base_domain'))
    ->middleware(IdentifyTenant::class)
    ->group(base_path('routes/tenant.php'));

// ─── Custom domain fallback ───
// When a custom domain (e.g. app.acme.com) hits the server,
// IdentifyTenant resolves the tenant via custom_domain column,
// then redirects to the subdomain URL.
Route::middleware(IdentifyTenant::class)
    ->get('/{any}', function () {
        $tenant = tenant();
        if (! $tenant) {
            abort(404);
        }
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443]) ? '' : ':' . $port;
        $path = '/' . request()->path();
        return redirect("{$scheme}://{$tenant->subdomain}." . config('app.base_domain') . "{$portSuffix}{$path}");
    })->where('any', '^(?!admin|telescope|login|logout|invitations|webhook|up|storage).*');
