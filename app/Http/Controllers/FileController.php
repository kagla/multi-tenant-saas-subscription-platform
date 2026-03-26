<?php

namespace App\Http\Controllers;

use App\Services\UsageTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FileController extends Controller
{
    public function index(): View
    {
        $tenant = tenant();
        $tracker = UsageTracker::for($tenant);

        $files = Storage::disk('local')->files("tenants/{$tenant->id}");
        $fileList = collect($files)->map(function ($path) {
            return [
                'name' => basename($path),
                'path' => $path,
                'size' => Storage::disk('local')->size($path),
                'modified' => Storage::disk('local')->lastModified($path),
            ];
        })->sortByDesc('modified')->values();

        return view('tenant.files.index', [
            'tenant' => $tenant,
            'files' => $fileList,
            'storageUsed' => $tracker->getTotalUsage('storage_mb'),
            'storageLimit' => $tenant->getPlanLimit('storage_mb'),
            'storagePercent' => $tracker->getUsagePercent('storage_mb'),
        ]);
    }

    public function upload(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // 50MB max per file
        ]);

        $tenant = tenant();
        $tracker = UsageTracker::for($tenant);
        $file = $request->file('file');
        $fileSizeMB = round($file->getSize() / (1024 * 1024), 4);

        // Check storage quota
        $currentUsage = $tracker->getTotalUsage('storage_mb');
        $limit = $tenant->getPlanLimit('storage_mb');

        if ($limit !== PHP_INT_MAX && ($currentUsage + $fileSizeMB) > $limit) {
            $errorMsg = "저장소 제한 초과. 사용량: " . round($currentUsage, 1) .
                "MB / {$limit}MB. 파일 크기: " . round($fileSizeMB, 1) . "MB.";

            if ($request->expectsJson()) {
                return response()->json(['error' => $errorMsg, 'upgrade_url' => route('tenant.subscription.plans', ['tenant' => $tenant->subdomain])], 422);
            }

            return back()->withErrors(['file' => $errorMsg]);
        }

        $path = $file->store("tenants/{$tenant->id}", 'local');

        $tracker->track('storage_mb', $fileSizeMB);

        if ($request->expectsJson()) {
            return response()->json(['path' => $path, 'size_mb' => $fileSizeMB]);
        }

        return back()->with('status', 'file-uploaded');
    }

    public function destroy(Request $request, string $tenantSubdomain, string $filename): RedirectResponse
    {
        $tenant = tenant();
        $path = "tenants/{$tenant->id}/{$filename}";

        if (! Storage::disk('local')->exists($path)) {
            return back()->withErrors(['file' => '파일을 찾을 수 없습니다.']);
        }

        $fileSizeMB = round(Storage::disk('local')->size($path) / (1024 * 1024), 4);
        Storage::disk('local')->delete($path);

        // Record negative usage to reduce storage count
        UsageTracker::for($tenant)->track('storage_mb', -$fileSizeMB);

        return back()->with('status', 'file-deleted');
    }
}
