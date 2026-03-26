<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-red-600 leading-tight">{{ __('Quota Exceeded') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 mb-2">Usage Limit Reached</h3>
                <p class="text-gray-500 mb-6">
                    You've reached the <strong>{{ $metric ?? 'resource' }}</strong> limit on your
                    <strong>{{ ucfirst(tenant()->plan) }}</strong> plan.
                </p>

                @if(isset($usage) && isset($limit))
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg inline-block">
                        <span class="text-3xl font-bold text-red-600">{{ number_format($usage) }}</span>
                        <span class="text-gray-400"> / {{ $limit === PHP_INT_MAX ? 'Unlimited' : number_format($limit) }}</span>
                    </div>
                @endif

                <div class="flex justify-center gap-4">
                    @if(tenant()->plan !== 'enterprise')
                        <a href="{{ route('tenant.subscription.plans', ['tenant' => tenant()->subdomain]) }}"
                           class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700">
                            Upgrade Plan
                        </a>
                    @endif
                    <a href="{{ route('tenant.usage', ['tenant' => tenant()->subdomain]) }}"
                       class="inline-flex items-center px-6 py-3 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        View Usage
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
