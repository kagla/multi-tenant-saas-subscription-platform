<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('보안이 필요한 영역입니다. 계속하려면 비밀번호를 확인해주세요.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('비밀번호')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('확인') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
