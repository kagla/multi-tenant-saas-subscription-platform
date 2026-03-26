@extends('admin.layout')
@section('title', 'Users')

@section('content')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-500 uppercase">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or email..."
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">Role</label>
                <select name="role" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">All</option>
                    @foreach(['owner','admin','member'] as $r)
                        <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold hover:bg-gray-700">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-xs text-gray-400">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if($user->tenant)
                                <a href="{{ url("/admin/tenants/{$user->tenant_id}") }}" class="text-blue-600 hover:text-blue-800">{{ $user->tenant->name }}</a>
                            @elseif($user->is_super_admin)
                                <span class="text-purple-600 font-medium">Super Admin</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                @if($user->is_super_admin) bg-purple-100 text-purple-800
                                @elseif($user->role==='owner') bg-yellow-100 text-yellow-800
                                @elseif($user->role==='admin') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $user->is_super_admin ? 'Super Admin' : ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ url("/admin/users/{$user->id}") }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
