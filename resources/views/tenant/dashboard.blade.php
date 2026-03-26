<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $currentTenant->name }} 대시보드
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @php
                $userCount = $currentTenant->users()->withoutGlobalScopes()->count();
                $apiLimit = $currentTenant->getPlanLimit('api_calls');
                $storageLimit = $currentTenant->getPlanLimit('storage_mb');
                $apiUsage = (int) $currentTenant->usageRecords()
                    ->withoutGlobalScopes()
                    ->where('metric', 'api_calls')
                    ->where('recorded_at', '>=', now()->startOfDay())
                    ->sum('value');
                $storageUsage = (float) $currentTenant->usageRecords()
                    ->withoutGlobalScopes()
                    ->where('metric', 'storage_mb')
                    ->sum('value');
                $apiPercent = $apiLimit === PHP_INT_MAX ? 0 : ($apiLimit > 0 ? min(100, round($apiUsage / $apiLimit * 100)) : 0);
                $storagePercent = $storageLimit === PHP_INT_MAX ? 0 : ($storageLimit > 0 ? min(100, round($storageUsage / $storageLimit * 100)) : 0);
            @endphp

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">서브도메인</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $currentTenant->subdomain }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">플랜</div>
                    <div class="text-2xl font-bold">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                            @if($currentTenant->plan === 'free') bg-blue-100 text-blue-800
                            @elseif($currentTenant->plan === 'pro') bg-yellow-100 text-yellow-800
                            @else bg-purple-100 text-purple-800 @endif">
                            {{ strtoupper($currentTenant->plan) }}
                        </span>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">멤버</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $userCount }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">상태</div>
                    <div class="text-2xl font-bold {{ $currentTenant->is_active ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currentTenant->is_active ? '활성' : '비활성' }}
                    </div>
                </div>
            </div>

            {{-- Usage --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">사용량</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">API 호출 (오늘)</span>
                            <span class="font-medium">{{ number_format($apiUsage) }} / {{ $apiLimit === PHP_INT_MAX ? '무제한' : number_format($apiLimit) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $apiPercent > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                                 style="width: {{ $apiPercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">저장소</span>
                            <span class="font-medium">{{ number_format($storageUsage, 1) }} MB / {{ $storageLimit === PHP_INT_MAX ? '무제한' : number_format($storageLimit) . ' MB' }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $storagePercent > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                                 style="width: {{ $storagePercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">기능</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach(['basic_dashboard', 'advanced_analytics', 'custom_domain', 'priority_support', 'sso', 'audit_log', 'dedicated_support'] as $feature)
                        <div class="flex items-center gap-2 text-sm">
                            @if($currentTenant->hasFeature($feature))
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            @endif
                            <span class="{{ $currentTenant->hasFeature($feature) ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ str_replace('_', ' ', ucfirst($feature)) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
