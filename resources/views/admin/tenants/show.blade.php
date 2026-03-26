@extends('admin.layout')
@section('title', $tenant->name)

@section('content')
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ $tenant->name }}</h3>
                <p class="text-gray-500">{{ $tenant->subdomain }}.{{ config('app.base_domain') }}
                    @if($tenant->domain) &middot; {{ $tenant->domain }} @endif
                </p>
            </div>
            <div class="flex gap-3">
                <form method="POST" action="{{ url("/admin/tenants/{$tenant->id}/impersonate") }}">
                    @csrf
                    <button class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm font-semibold hover:bg-yellow-400">가장 로그인</button>
                </form>
                @if($tenant->is_active)
                    <form method="POST" action="{{ url("/admin/tenants/{$tenant->id}/suspend") }}" onsubmit="return confirm('이 테넌트를 정지하시겠습니까?')">
                        @csrf @method('PATCH')
                        <button class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-500">정지</button>
                    </form>
                @else
                    <form method="POST" action="{{ url("/admin/tenants/{$tenant->id}/activate") }}">
                        @csrf @method('PATCH')
                        <button class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-500">활성화</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="mt-4 grid grid-cols-4 gap-4">
            <div><span class="text-xs text-gray-500 uppercase">플랜</span>
                <p class="font-semibold">{{ ucfirst($tenant->plan) }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">상태</span>
                <p class="font-semibold {{ $tenant->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $tenant->is_active ? '활성' : '정지' }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">오늘 API</span>
                <p class="font-semibold">{{ number_format($usage['api_today']) }} / {{ $usage['api_limit'] === PHP_INT_MAX ? '&infin;' : number_format($usage['api_limit']) }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">저장소</span>
                <p class="font-semibold">{{ number_format($usage['storage'], 1) }} MB / {{ $usage['storage_limit'] === PHP_INT_MAX ? '&infin;' : number_format($usage['storage_limit']) . ' MB' }}</p></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Members --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200"><h4 class="font-medium">Members ({{ $members->count() }})</h4></div>
            <div class="divide-y divide-gray-100">
                @foreach($members as $member)
                    <div class="px-4 py-3 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                            <p class="text-xs text-gray-400">{{ $member->email }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if($member->role==='owner') bg-purple-100 text-purple-800
                            @elseif($member->role==='admin') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800 @endif">{{ ucfirst($member->role) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Subscriptions --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b border-gray-200"><h4 class="font-medium">Subscription History</h4></div>
            @if($subscriptions->isEmpty())
                <div class="p-4 text-gray-400 text-sm">No subscriptions.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($subscriptions as $sub)
                        <div class="px-4 py-3">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium">{{ $sub->stripe_plan ?? 'N/A' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $sub->stripe_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $sub->stripe_status }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Created {{ $sub->created_at->diffForHumans() }}
                                @if($sub->ends_at) &middot; Ends {{ $sub->ends_at->format('M j, Y') }} @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Audit Log --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200"><h4 class="font-medium">Activity Log</h4></div>
        <div class="divide-y divide-gray-100 max-h-80 overflow-auto">
            @forelse($logs as $log)
                <div class="px-4 py-3">
                    <p class="text-sm text-gray-900">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}
                        @if($log->user) &middot; {{ $log->user->name }} @endif
                        @if($log->ip_address) &middot; {{ $log->ip_address }} @endif
                    </p>
                </div>
            @empty
                <div class="p-4 text-gray-400 text-sm">No activity.</div>
            @endforelse
        </div>
    </div>
@endsection
