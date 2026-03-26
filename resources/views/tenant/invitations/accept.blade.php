<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        You've been invited to join <strong>{{ $invitation->tenant->name }}</strong> as a <strong>{{ $invitation->role }}</strong>.
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
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" :value="$invitation->email" disabled />
            </div>

            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mb-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
            </div>
        @else
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
                An account with <strong>{{ $invitation->email }}</strong> already exists. Clicking accept will link your account to this organization.
            </div>
        @endif

        <x-primary-button class="w-full justify-center">
            {{ __('Accept Invitation') }}
        </x-primary-button>
    </form>
</x-guest-layout>
