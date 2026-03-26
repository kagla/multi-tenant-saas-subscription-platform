<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            구독이 활성화되었습니다
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

                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ ucfirst($plan) }}에 오신 것을 환영합니다!</h3>
                <p class="text-gray-500 mb-6">
                    구독이 성공적으로 활성화되었습니다. 이제 {{ ucfirst($plan) }}의 모든 기능을 이용하실 수 있습니다.
                </p>

                <div class="flex justify-center gap-4">
                    <a href="{{ route('tenant.subscription.index', ['tenant' => $tenant->subdomain]) }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        구독 확인
                    </a>
                    <a href="{{ route('tenant.dashboard', ['tenant' => $tenant->subdomain]) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        대시보드로 이동
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
