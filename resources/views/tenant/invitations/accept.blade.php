<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        <strong>{{ $invitation->tenant->name }}</strong>에 <strong>{{ $invitation->role }}</strong>(으)로 초대되었습니다.
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @php
        $existingUser = \App\Models\User::withoutGlobalScopes()->where('email', $invitation->email)->exists();
    @endphp

    <form method="POST" action="{{ url("/invitations/{$invitation->token}/accept") }}">
        @csrf

        @if(!$existingUser)
            <div class="mb-4">
                <x-input-label for="name" value="이름" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="email" value="이메일" />
                <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" :value="$invitation->email" disabled />
            </div>

            <div class="mb-4">
                <x-input-label for="password" value="비밀번호" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="password_confirmation" value="비밀번호 확인" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            </div>
        @else
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
                <strong>{{ $invitation->email }}</strong> 계정이 이미 존재합니다. 수락을 클릭하면 계정이 이 조직에 연결됩니다.
            </div>
        @endif

        <x-primary-button class="w-full justify-center">
            초대 수락
        </x-primary-button>
    </form>
</x-guest-layout>
