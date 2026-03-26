@extends('admin.layout')
@section('title', '테넌트')

@section('content')
    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-500 uppercase">검색</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="이름 또는 서브도메인..."
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">플랜</label>
                <select name="plan" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">전체</option>
                    @foreach(['free','pro','enterprise'] as $p)
                        <option value="{{ $p }}" {{ request('plan') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 uppercase">상태</label>
                <select name="status" class="mt-1 block rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">전체</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>활성</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>정지</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold hover:bg-gray-700">필터</button>
            @if(request()->hasAny(['search','plan','status']))
                <a href="{{ url('/admin/tenants') }}" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-md text-sm hover:bg-gray-50">초기화</a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">테넌트</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">플랜</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">멤버</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">상태</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">생성일</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">작업</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tenants as $tenant)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                            <div class="text-xs text-gray-400">{{ $tenant->subdomain }}.{{ config('app.base_domain') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($tenant->plan === 'free') bg-gray-100 text-gray-800
                                @elseif($tenant->plan === 'pro') bg-blue-100 text-blue-800
                                @else bg-purple-100 text-purple-800 @endif">
                                {{ ucfirst($tenant->plan) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $tenant->users_count }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tenant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $tenant->is_active ? '활성' : '정지' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $tenant->created_at->format('M j, Y') }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ url("/admin/tenants/{$tenant->id}") }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">상세</a>
                            <form method="POST" action="{{ url("/admin/tenants/{$tenant->id}/impersonate") }}" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">가장 로그인</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">테넌트를 찾을 수 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tenants->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
