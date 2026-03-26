<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Organization Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('status') === 'settings-updated')
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                            Settings updated successfully.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.settings.update', ['tenant' => $tenant->subdomain]) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <x-input-label for="name" :value="__('Organization Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $tenant->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="subdomain" :value="__('Subdomain')" />
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <x-text-input id="subdomain" name="subdomain" type="text" class="block w-full rounded-r-none"
                                    :value="old('subdomain', $tenant->subdomain)" required />
                                <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                    .{{ config('app.base_domain') }}
                                </span>
                            </div>
                            <x-input-error :messages="$errors->get('subdomain')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label :value="__('Current Plan')" />
                            <div class="mt-1">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($tenant->plan === 'free') bg-blue-100 text-blue-800
                                    @elseif($tenant->plan === 'pro') bg-yellow-100 text-yellow-800
                                    @else bg-purple-100 text-purple-800
                                    @endif">
                                    {{ strtoupper($tenant->plan) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
