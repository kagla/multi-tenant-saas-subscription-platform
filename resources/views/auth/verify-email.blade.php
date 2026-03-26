<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('가입해 주셔서 감사합니다! 시작하기 전에, 방금 보내드린 이메일의 링크를 클릭하여 이메일 주소를 인증해주세요. 이메일을 받지 못하셨다면, 다시 보내드리겠습니다.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('새로운 인증 링크가 가입 시 입력하신 이메일 주소로 발송되었습니다.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('인증 이메일 재발송') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('로그아웃') }}
            </button>
        </form>
    </div>
</x-guest-layout>
