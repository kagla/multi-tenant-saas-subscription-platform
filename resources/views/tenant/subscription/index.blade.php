<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            구독 & 결제
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Messages --}}
            @if (session('status') === 'subscription-cancelled')
                <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">
                    구독이 취소되었습니다. 결제 기간이 끝날 때까지 계속 이용하실 수 있습니다.
                </div>
            @elseif (session('status') === 'subscription-resumed')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    구독이 재개되었습니다.
                </div>
            @elseif (session('status') === 'subscription-upgraded')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    플랜이 업그레이드되었습니다.
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Current Plan --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">현재 플랜</h3>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                @if($tenant->plan === 'free') bg-gray-100 text-gray-800
                                @elseif($tenant->plan === 'pro') bg-blue-100 text-blue-800
                                @else bg-purple-100 text-purple-800
                                @endif">
                                {{ strtoupper($tenant->plan) }}
                            </span>
                            <span class="text-2xl font-bold text-gray-900">
                                {{ number_format($plans[$tenant->plan]['price_krw'] ?? $plans[$tenant->plan]['price']) }}원/월
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">{{ $plans[$tenant->plan]['description'] }}</p>
                    </div>
                    <div class="flex gap-3">
                        @if($tenant->plan !== 'enterprise')
                            <a href="{{ route('tenant.subscription.plans', ['tenant' => $tenant->subdomain]) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                {{ $tenant->plan === 'free' ? '업그레이드' : '플랜 변경' }}
                            </a>
                        @endif

                        @if($subscription && !$subscription->ends_at)
                            @php
                                $cancelRoute = config('services.pg.driver') === 'inicis'
                                    ? route('tenant.inicis.cancel', ['tenant' => $tenant->subdomain])
                                    : route('tenant.subscription.cancel', ['tenant' => $tenant->subdomain]);
                            @endphp
                            <form method="POST" action="{{ $cancelRoute }}"
                                  onsubmit="return confirm('구독을 취소하시겠습니까?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                                    구독 취소
                                </button>
                            </form>
                        @elseif($subscription && $subscription->ends_at)
                            <form method="POST" action="{{ route('tenant.subscription.resume', ['tenant' => $tenant->subdomain]) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500">
                                    구독 재개
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($tenant->onTrial())
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                        체험 기간 종료 {{ $tenant->trial_ends_at->diffForHumans() }} ({{ $tenant->trial_ends_at->format('Y년 n월 j일') }})
                    </div>
                @endif

                @if($subscription && $subscription->ends_at)
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
                        구독이 취소되었으며 {{ $subscription->ends_at->format('Y년 n월 j일') }}에 종료됩니다.
                    </div>
                @endif
            </div>

            {{-- Usage --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">사용량</h3>
                <div class="space-y-4">
                    @php
                        $metrics = [
                            ['key' => 'api_calls_per_day', 'label' => 'API 호출 (오늘)', 'current' => $usage['api_calls'], 'unit' => ''],
                            ['key' => 'storage_mb', 'label' => '저장소', 'current' => $usage['storage_mb'], 'unit' => ' MB'],
                            ['key' => 'members', 'label' => '팀 멤버', 'current' => $usage['members'], 'unit' => ''],
                        ];
                    @endphp
                    @foreach($metrics as $metric)
                        @php
                            $limit = $tenant->getPlanLimit($metric['key']);
                            $isUnlimited = $limit === PHP_INT_MAX;
                            $percent = $isUnlimited ? 0 : ($limit > 0 ? min(100, round($metric['current'] / $limit * 100)) : 0);
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">{{ $metric['label'] }}</span>
                                <span class="font-medium">
                                    {{ number_format($metric['current']) }}{{ $metric['unit'] }}
                                    / {{ $isUnlimited ? '무제한' : number_format($limit) . $metric['unit'] }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $percent > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                                     style="width: {{ $isUnlimited ? 0 : $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Features --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">포함된 기능</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @php
                        $allFeatures = ['basic_dashboard', 'community_support', 'advanced_analytics', 'priority_support', 'custom_domain', 'api_access', 'sso', 'audit_log', 'dedicated_support'];
                    @endphp
                    @foreach($allFeatures as $feature)
                        <div class="flex items-center gap-2 text-sm">
                            @if($tenant->hasFeature($feature))
                                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-900">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                            @else
                                <svg class="w-5 h-5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="text-gray-400">{{ str_replace('_', ' ', ucfirst($feature)) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
