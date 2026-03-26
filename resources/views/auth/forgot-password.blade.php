<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('비밀번호를 잊으셨나요? 걱정 마세요. 이메일 주소를 입력하시면 비밀번호 재설정 링크를 보내드립니다.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('이메일')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('비밀번호 재설정 링크 발송') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
