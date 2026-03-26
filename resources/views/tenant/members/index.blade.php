<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Members') }}
            </h2>
            @can('manageMembers', $tenant)
                <a href="{{ route('tenant.invitations.create', ['tenant' => $tenant->subdomain]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Invite Member') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status') === 'role-updated')
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    Member role updated successfully.
                </div>
            @endif
            @if (session('status') === 'member-removed')
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    Member removed successfully.
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                @can('manageMembers', $tenant)
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($members as $member)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $member->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($member->role === 'owner') bg-purple-100 text-purple-800
                                            @elseif($member->role === 'admin') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($member->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $member->created_at->format('Y-m-d') }}
                                    </td>
                                    @can('manageMembers', $tenant)
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm" x-data="{ confirmDelete: false }">
                                            @if($member->role !== 'owner')
                                                <div class="flex items-center justify-end gap-3">
                                                    <form method="POST" action="{{ route('tenant.members.updateRole', ['tenant' => $tenant->subdomain, 'user' => $member->id]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="role" onchange="this.form.submit()"
                                                            class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="admin" {{ $member->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                            <option value="member" {{ $member->role === 'member' ? 'selected' : '' }}>Member</option>
                                                        </select>
                                                    </form>

                                                    @if($member->id !== auth()->id())
                                                        <template x-if="!confirmDelete">
                                                            <button @click="confirmDelete = true"
                                                                class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                                Remove
                                                            </button>
                                                        </template>
                                                        <template x-if="confirmDelete">
                                                            <form method="POST" action="{{ route('tenant.members.destroy', ['tenant' => $tenant->subdomain, 'user' => $member->id]) }}"
                                                                class="flex items-center gap-2">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-bold">
                                                                    Confirm
                                                                </button>
                                                                <button type="button" @click="confirmDelete = false"
                                                                    class="text-gray-500 hover:text-gray-700 text-sm">
                                                                    Cancel
                                                                </button>
                                                            </form>
                                                        </template>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">-</span>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
