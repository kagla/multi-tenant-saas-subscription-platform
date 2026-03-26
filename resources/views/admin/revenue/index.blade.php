@extends('admin.layout')
@section('title', '수익')

@section('content')
    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">MRR (월간 반복 수익)</p>
            <p class="text-3xl font-bold text-green-600">${{ number_format($mrr) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">ARR (연간 반복 수익)</p>
            <p class="text-3xl font-bold text-green-600">${{ number_format($arr) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">활성 구독</p>
            <p class="text-3xl font-bold text-gray-900">{{ $activeSubscriptions }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <p class="text-sm text-gray-500">이탈률</p>
            <p class="text-3xl font-bold {{ $churnRate > 5 ? 'text-red-600' : 'text-gray-900' }}">{{ $churnRate }}%</p>
            <p class="text-xs text-gray-400 mt-1">{{ $churnedCount }} 이탈 (30일)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Monthly Revenue Chart --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">월별 수익</h3>
            <div style="height: 280px;"><canvas id="revenueChart"></canvas></div>
        </div>

        {{-- Plan Revenue Pie --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">플랜별 수익</h3>
            <div style="height: 280px;"><canvas id="planRevenueChart"></canvas></div>
        </div>
    </div>

    {{-- Plan Breakdown Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200"><h3 class="font-medium">플랜 분석</h3></div>
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">플랜</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">테넌트</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">월 단가</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">월 수익</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($planRevenue as $plan => $data)
                    <tr>
                        <td class="px-6 py-3 text-sm font-medium">{{ ucfirst($plan) }}</td>
                        <td class="px-6 py-3 text-sm text-right">{{ $data['count'] }}</td>
                        <td class="px-6 py-3 text-sm text-right">${{ $data['price'] }}</td>
                        <td class="px-6 py-3 text-sm text-right font-semibold">${{ number_format($data['revenue']) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-gray-50 font-bold">
                    <td class="px-6 py-3 text-sm" colspan="3">전체 MRR</td>
                    <td class="px-6 py-3 text-sm text-right">${{ number_format($mrr) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyData = @json($monthlyRevenue);
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(monthlyData).map(m => { const [y,mo] = m.split('-'); return new Date(y,mo-1).toLocaleDateString('en',{month:'short',year:'2-digit'}); }),
            datasets: [{ label: '수익', data: Object.values(monthlyData), backgroundColor: 'rgba(16,185,129,0.5)', borderColor: 'rgb(16,185,129)', borderWidth: 1, borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v } } } }
    });

    const planData = @json(collect($planRevenue)->mapWithKeys(fn($d,$k) => [ucfirst($k) => $d['revenue']]));
    new Chart(document.getElementById('planRevenueChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(planData),
            datasets: [{ data: Object.values(planData), backgroundColor: ['#9ca3af','#3b82f6','#8b5cf6'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
@endpush
