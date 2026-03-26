<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Choose a Plan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('status') === 'subscription-required')
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">
                    A subscription is required to access this feature. Please choose a plan below.
                </div>
            @elseif (session('status') === 'plan-upgrade-required')
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg">
                    Your current plan doesn't include this feature. Please upgrade to continue.
                </div>
            @endif

            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-900">Simple, transparent pricing</h3>
                <p class="mt-2 text-gray-500">Choose the plan that's right for your team</p>
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
                                    Most Popular
                                </span>
                            </div>
                        @endif

                        @if($isCurrent)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="bg-green-500 text-white text-xs font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                                    Current Plan
                                </span>
                            </div>
                        @endif

                        <div class="text-center">
                            <h4 class="text-xl font-bold text-gray-900">{{ $plan['name'] }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ $plan['description'] }}</p>
                            <div class="mt-4">
                                <span class="text-4xl font-extrabold text-gray-900">${{ $plan['price'] }}</span>
                                <span class="text-gray-500">/mo</span>
                            </div>
                            @if($plan['trial_days'] > 0 && $tenant->plan === 'free')
                                <p class="mt-1 text-sm text-blue-600">{{ $plan['trial_days'] }}-day free trial</p>
                            @endif
                        </div>

                        <div class="mt-6 space-y-3 flex-1">
                            <p class="text-sm font-medium text-gray-700 uppercase tracking-wider">Limits</p>
                            <div class="text-sm text-gray-600 space-y-2">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $plan['limits']['api_calls_per_day'] === PHP_INT_MAX ? 'Unlimited' : number_format($plan['limits']['api_calls_per_day']) }} API calls/day
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    @if($plan['limits']['storage_mb'] === PHP_INT_MAX)
                                        Unlimited storage
                                    @elseif($plan['limits']['storage_mb'] >= 1024)
                                        {{ $plan['limits']['storage_mb'] / 1024 }} GB storage
                                    @else
                                        {{ $plan['limits']['storage_mb'] }} MB storage
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ $plan['limits']['members'] === PHP_INT_MAX ? 'Unlimited' : $plan['limits']['members'] }} team members
                                </div>
                            </div>

                            <p class="text-sm font-medium text-gray-700 uppercase tracking-wider pt-2">Features</p>
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
                                    Current Plan
                                </button>
                            @elseif($key === 'free')
                                {{-- Downgrade to free not directly available --}}
                                <button disabled class="w-full py-3 px-4 rounded-lg text-sm font-semibold bg-gray-100 text-gray-400 cursor-not-allowed">
                                    {{ $tenant->plan === 'free' ? 'Current Plan' : 'Cancel to downgrade' }}
                                </button>
                            @else
                                <form method="POST" action="{{ $tenant->activeSubscription
                                    ? route('tenant.subscription.upgrade', ['tenant' => $tenant->subdomain])
                                    : route('tenant.subscription.checkout', ['tenant' => $tenant->subdomain]) }}">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <button type="submit"
                                        class="w-full py-3 px-4 rounded-lg text-sm font-semibold
                                            {{ $isPopular ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-800 text-white hover:bg-gray-700' }}">
                                        @if($tenant->plan === 'free')
                                            Start {{ $plan['trial_days'] }}-day Trial
                                        @elseif($key === 'enterprise')
                                            Upgrade to Enterprise
                                        @else
                                            Switch to Pro
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
                    <h3 class="text-lg font-bold text-gray-900">Feature Comparison</h3>
                </div>
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-3 px-6 text-left text-sm font-medium text-gray-500">Feature</th>
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
                            <td class="py-3 px-6 text-sm text-gray-700">API Calls/day</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    {{ $plan['limits']['api_calls_per_day'] === PHP_INT_MAX ? 'Unlimited' : number_format($plan['limits']['api_calls_per_day']) }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="py-3 px-6 text-sm text-gray-700">Storage</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    @if($plan['limits']['storage_mb'] === PHP_INT_MAX)
                                        Unlimited
                                    @elseif($plan['limits']['storage_mb'] >= 1024)
                                        {{ $plan['limits']['storage_mb'] / 1024 }} GB
                                    @else
                                        {{ $plan['limits']['storage_mb'] }} MB
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="py-3 px-6 text-sm text-gray-700">Team Members</td>
                            @foreach($plans as $plan)
                                <td class="py-3 px-6 text-center text-sm font-medium">
                                    {{ $plan['limits']['members'] === PHP_INT_MAX ? 'Unlimited' : $plan['limits']['members'] }}
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
