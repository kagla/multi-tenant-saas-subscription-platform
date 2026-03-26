@extends('admin.layout')
@section('title', $user->name)

@section('content')
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h3>
        <p class="text-gray-500">{{ $user->email }}</p>
        <div class="mt-4 grid grid-cols-4 gap-4">
            <div><span class="text-xs text-gray-500 uppercase">역할</span>
                <p class="font-semibold">{{ $user->is_super_admin ? '슈퍼 관리자' : ucfirst($user->role) }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">테넌트</span>
                <p class="font-semibold">{{ $user->tenant?->name ?? '-' }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">가입일</span>
                <p class="font-semibold">{{ $user->created_at->format('Y-m-d') }}</p></div>
            <div><span class="text-xs text-gray-500 uppercase">이메일 인증</span>
                <p class="font-semibold">{{ $user->email_verified_at ? '예' : '아니오' }}</p></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200"><h4 class="font-medium">활동 로그</h4></div>
        <div class="divide-y divide-gray-100 max-h-96 overflow-auto">
            @forelse($logs as $log)
                <div class="px-4 py-3">
                    <p class="text-sm text-gray-900">{{ $log->description }}</p>
                    <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }} &middot; {{ $log->ip_address }}</p>
                </div>
            @empty
                <div class="p-4 text-gray-400 text-sm">활동 없음</div>
            @endforelse
        </div>
    </div>
@endsection
