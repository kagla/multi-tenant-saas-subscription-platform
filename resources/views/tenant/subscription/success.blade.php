<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Activated') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome to {{ ucfirst($plan) }}!</h3>
                <p class="text-gray-500 mb-6">
                    Your subscription has been activated successfully. You now have access to all
                    {{ ucfirst($plan) }} features.
                </p>

                <div class="flex justify-center gap-4">
                    <a href="{{ route('tenant.subscription.index', ['tenant' => $tenant->subdomain]) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        View Subscription
                    </a>
                    <a href="{{ route('tenant.dashboard', ['tenant' => $tenant->subdomain]) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
