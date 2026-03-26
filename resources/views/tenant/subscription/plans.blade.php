<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            플랜 선택
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status') === 'subscription-required')
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">
                    이 기능을 이용하려면 구독이 필요합니다. 아래에서 플랜을 선택해 주세요.
                </div>
            @elseif (session('status') === 'plan-upgrade-required')
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">
                    현재 플랜에는 이 기능이 포함되어 있지 않습니다. 업그레이드해 주세요.
                </div>
            @endif

            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-900">합리적인 요금제</h3>
                <p class="mt-2 text-gray-500">팀에 맞는 플랜을 선택하세요</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($plans as $key => $plan)
                    @php
                        $isCurrent = $tenant->plan === $key;
                        $isPopular = $key === 'pro';
                    @endphp
                    <div class="relative bg-white rounded-2xl shadow-sm border-2 {{ $isPopular ? 'border-blue-500' : ($isCurrent ? 'border-green-500' : 'border-gray-200') }} p-8 flex flex-col">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="bg-blue-500 text-white text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                                    인기
                                </span>
                            </div>
                        @endif

                        @if($isCurrent)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="bg-green-500 text-white text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                                    현재 플랜
                                </span>
                            </div>
                        @endif

                        <div class="text-center">
                            <h4 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ $plan['description'] }}</p>
                            <div class="mt-4">
                                <span class="text-4xl font-extrabold text-gray-900">{{ number_format($plan['price_krw'] ?? $plan['price']) }}원</span>
                                <span class="text-gray-500">/월</span>
                            </div>
                            @if($plan['trial_days'] > 0 && $tenant->plan === 'free')
                                <p class="mt-1 text-sm text-blue-600">{{ $plan['trial_days'] }}일 무료 체험</p>
                            @endif
                        </div>

                        <div class="mt-6 space-y-3 flex-1">
                            <p class="text-sm font-medium text-gray-700 uppercase tracking-wider">제한</p>
                            <div class="text-sm text-gray-600 space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $plan['limits']['api_calls_per_day'] === PHP_INT_MAX ? '무제한' : number_format($plan['limits']['api_calls_per_day']) }} API 호출/일
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @if($plan['limits']['storage_mb'] === PHP_INT_MAX)
                                        무제한 저장소
                                    @elseif($plan['limits']['storage_mb'] >= 1024)
                                        {{ $plan['limits']['storage_mb'] / 1024 }} GB 저장소
                                    @else
                                        {{ $plan['limits']['storage_mb'] }} MB 저장소
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $plan['limits']['members'] === PHP_INT_MAX ? '무제한' : $plan['limits']['members'] }} 팀 멤버
                                </div>
                            </div>

                            <p class="text-sm font-medium text-gray-700 uppercase tracking-wider pt-2">기능</p>
                            <div class="text-sm text-gray-600 space-y-2">
                                @foreach($plan['features'] as $feature)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ str_replace('_', ' ', ucfirst($feature)) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-8">
                            @if($isCurrent)
                                <button disabled class="w-full py-3 px-4 rounded-lg text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                                    현재 플랜
                                </button>
                            @elseif($key === 'free')
                                {{-- Downgrade to free not directly available --}}
                                <button disabled class="w-full py-3 px-4 rounded-lg text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                                    {{ $tenant->plan === 'free' ? '현재 플랜' : '다운그레이드하려면 취소하세요' }}
                                </button>
                            @else
                                @php
                                    $pgDriver = config('services.pg.driver', 'inicis');
                                    if ($pgDriver === 'inicis') {
                                        $checkoutRoute = $tenant->activeSubscription
                                            ? route('tenant.inicis.billing.checkout', ['tenant' => $tenant->subdomain])
                                            : route('tenant.inicis.billing.checkout', ['tenant' => $tenant->subdomain]);
                                    } else {
                                        $checkoutRoute = $tenant->activeSubscription
                                            ? route('tenant.subscription.upgrade', ['tenant' => $tenant->subdomain])
                                            : route('tenant.subscription.checkout', ['tenant' => $tenant->subdomain]);
                                    }
                                @endphp
                                <form method="POST" action="{{ $checkoutRoute }}">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <button type="submit"
                                        class="w-full py-3 px-4 rounded-lg text-sm font-semibold
                                            {{ $isPopular ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-800 text-white hover:bg-gray-700' }}">
                                        @if($tenant->plan === 'free')
                                            {{ $plan['trial_days'] }}일 체험 시작
                                        @elseif($key === 'enterprise')
                                            Enterprise로 업그레이드
                                        @else
                                            Pro로 변경
                                        @endif
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Feature Comparison Table --}}
            <div class="mt-16 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">기능 비교</h3>
                </div>
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-500">기능</th>
                            @foreach($plans as $key => $plan)
                                <th class="py-3 px-6 text-center text-sm font-medium text-gray-900">{{ $plan['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $allFeatures = ['basic_dashboard', 'community_support', 'advanced_analytics', 'priority_support', 'custom_domain', 'api_access', 'sso', 'audit_log', 'dedicated_support'];
                        @endphp
                        @foreach($allFeatures as $feature)
                            <tr>
                                <td class="py-3 px-6 text-sm text-gray-700">{{ str_replace('_', ' ', ucfirst($feature)) }}</td>
                                @foreach($plans as $key => $plan)
                                    <td class="py-3 px-6 text-center">
                                        @if(in_array($feature, $plan['features']))
                                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td class="py-3 px-6 text-sm text-gray-700">API 호출/일</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    {{ $plan['limits']['api_calls_per_day'] === PHP_INT_MAX ? '무제한' : number_format($plan['limits']['api_calls_per_day']) }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="py-3 px-6 text-sm text-gray-700">저장소</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    @if($plan['limits']['storage_mb'] === PHP_INT_MAX)
                                        무제한
                                    @elseif($plan['limits']['storage_mb'] >= 1024)
                                        {{ $plan['limits']['storage_mb'] / 1024 }} GB
                                    @else
                                        {{ $plan['limits']['storage_mb'] }} MB
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="py-3 px-6 text-sm text-gray-700">팀 멤버</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    {{ $plan['limits']['members'] === PHP_INT_MAX ? '무제한' : $plan['limits']['members'] }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
