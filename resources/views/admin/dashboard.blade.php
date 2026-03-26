@extends('admin.layout')
@section('title', '대시보드')

@section('content')
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">전체 테넌트</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalTenants }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $activeTenants }} 활성</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">전체 사용자</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalUsers }}</p>
            <p class="text-xs text-gray-400 mt-1">DAU: {{ $dau }} / MAU: {{ $mau }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">MRR (월간 반복 수익)</p>
            <p class="text-3xl font-bold text-green-600">${{ number_format($mrr) }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $activeSubscriptions }} 활성 구독</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">플랜 분포</p>
            <div class="mt-2 space-y-1">
                @foreach(['free' => 'gray', 'pro' => 'blue', 'enterprise' => 'purple'] as $plan => $color)
                    <div class="flex justify-between text-sm">
                        <span class="text-{{ $color }}-600 font-medium">{{ ucfirst($plan) }}</span>
                        <span class="font-semibold">{{ $planDistribution[$plan] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Signup Chart --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">신규 가입 (30일)</h3>
            <div style="height: 250px;"><canvas id="signupChart"></canvas></div>
        </div>

        {{-- Plan Distribution Pie --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">플랜 분포</h3>
            <div style="height: 250px;"><canvas id="planChart"></canvas></div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">최근 활동</h3>
        </div>
        <div class="divide-y divide-gray-100 max-h-96 overflow-auto">
            @forelse($recentLogs as $log)
                <div class="px-4 py-3 flex items-start gap-3">
                    <div class="shrink-0 mt-0.5">
                        @if(str_contains($log->action, 'created'))
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 text-green-600 text-xs font-bold">+</span>
                        @elseif(str_contains($log->action, 'deleted') || str_contains($log->action, 'suspended'))
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-600 text-xs font-bold">-</span>
                        @else
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-100 text-blue-600 text-xs font-bold">~</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-900">{{ $log->description }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $log->created_at->diffForHumans() }}
                            @if($log->user) &middot; {{ $log->user->name }} @endif
                            @if($log->tenant) &middot; {{ $log->tenant->name }} @endif
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-gray-400 text-sm">활동 없음</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const signupData = @json($signupChart);
    new Chart(document.getElementById('signupChart'), {
        type: 'line',
        data: {
            labels: Object.keys(signupData).map(d => { const dt = new Date(d+'T00:00:00'); return dt.toLocaleDateString('en',{month:'short',day:'numeric'}); }),
            datasets: [{
                label: '신규 테넌트',
                data: Object.values(signupData),
                borderColor: 'rgb(59,130,246)',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true, tension: 0.3, borderWidth: 2, pointRadius: 0,
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { maxTicksLimit: 8 } } } }
    });

    const planData = @json($planDistribution);
    new Chart(document.getElementById('planChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(planData).map(p => p.charAt(0).toUpperCase() + p.slice(1)),
            datasets: [{ data: Object.values(planData), backgroundColor: ['#9ca3af','#3b82f6','#8b5cf6'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
@endpush
