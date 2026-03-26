@extends('admin.layout')
@section('title', $user->name)

@section('content')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h3>
        <p class="text-gray-500">{{ $user->email }}</p>
        <div class="mt-4 grid grid-cols-4 gap-4">
            <div><span class="text-xs text-gray-500 uppercase">Role</span>
                <p class="font-semibold">{{ $user->is_super_admin ? 'Super Admin' : ucfirst($user->role) }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">Tenant</span>
                <p class="font-semibold">{{ $user->tenant?->name ?? '-' }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">Joined</span>
                <p class="font-semibold">{{ $user->created_at->format('M j, Y') }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">Email Verified</span>
                <p class="font-semibold">{{ $user->email_verified_at ? 'Yes' : 'No' }}</p></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200"><h4 class="font-medium">Activity Log</h4></div>
        <div class="divide-y divide-gray-100 max-h-96 overflow-auto">
            @forelse($logs as $log)
                <div class="px-4 py-3">
                    <p class="text-sm text-gray-900">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }} &middot; {{ $log->ip_address }}</p>
                </div>
            @empty
                <div class="p-4 text-gray-400 text-sm">No activity.</div>
            @endforelse
        </div>
    </div>
@endsection
