<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            멤버 초대
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if (session('status') === 'invitation-sent')
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                            초대가 발송되었습니다!
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.invitations.store', ['tenant' => $tenant->subdomain]) }}">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="email" value="이메일 주소" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                :value="old('email')" required placeholder="colleague@example.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="role" value="역할" />
                            <select id="role" name="role"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>멤버</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>관리자</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">관리자는 멤버와 초대를 관리할 수 있습니다. 멤버는 일반 접근 권한을 갖습니다.</p>
                        </div>

                        <x-primary-button>초대 발송</x-primary-button>
                    </form>
                </div>
            </div>

            @if($invitations->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">대기 중인 초대</h3>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">이메일</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">역할</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">만료</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($invitations as $invitation)
                                    <tr>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $invitation->email }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $invitation->role === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $invitation->role === 'admin' ? '관리자' : '멤버' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $invitation->expires_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
