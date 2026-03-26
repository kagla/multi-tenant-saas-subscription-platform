<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">사용량 대시보드</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Usage Meters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($metrics as $key => $metric)
                    @php
                        $isUnlimited = $metric['limit'] === PHP_INT_MAX;
                        $unit = $metric['unit'] ?? '';
                        $barColor = $metric['percent'] > 90 ? 'bg-red-500' : ($metric['percent'] > 70 ? 'bg-yellow-500' : 'bg-blue-500');
                        $bgColor = $metric['percent'] > 90 ? 'bg-red-50 border-red-200' : ($metric['percent'] > 70 ? 'bg-yellow-50 border-yellow-200' : 'bg-white border-gray-200');
                    @endphp
                    <div class="overflow-hidden shadow-sm sm:rounded-lg p-6 border {{ $bgColor }}">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-600">{{ $metric['label'] }}</h3>
                            @if(!$isUnlimited && $metric['percent'] >= 80)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $metric['percent'] >= 100 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $metric['percent'] >= 100 ? '제한 도달' : '제한 임박' }}
                                </span>
                            @endif
                        </div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ number_format($metric['current'], $unit === 'MB' ? 1 : 0) }}{{ $unit }}
                            <span class="text-sm font-normal text-gray-500">
                                / {{ $isUnlimited ? '무제한' : number_format($metric['limit']) . $unit }}
                            </span>
                        </div>
                        @if(!$isUnlimited)
                            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                                <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-500"
                                     style="width: {{ min(100, $metric['percent']) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ number_format($metric['remaining'], $unit === 'MB' ? 1 : 0) }}{{ $unit }} 남음
                            </p>
                        @else
                            <div class="mt-3 w-full bg-green-100 rounded-full h-2.5">
                                <div class="bg-green-400 h-2.5 rounded-full" style="width: 5%"></div>
                            </div>
                            <p class="mt-1 text-xs text-green-600">{{ ucfirst($tenant->plan) }} 플랜에서 무제한</p>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- API Calls Chart --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">API 호출 (최근 30일)</h3>
                    <span id="chart-total" class="text-sm text-gray-500"></span>
                </div>
                <div style="height: 300px;">
                    <canvas id="apiChart"></canvas>
                </div>
            </div>

            {{-- Storage Breakdown --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">저장소 사용량</h3>
                    <div class="flex items-center justify-center" style="height: 220px;">
                        <canvas id="storageChart"></canvas>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">플랜 상세</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">현재 플랜</dt>
                            <dd class="text-sm font-semibold">{{ ucfirst($tenant->plan) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">API 호출/일</dt>
                            <dd class="text-sm font-semibold">
                                {{ $metrics['api_calls_per_day']['limit'] === PHP_INT_MAX ? '무제한' : number_format($metrics['api_calls_per_day']['limit']) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">저장소</dt>
                            <dd class="text-sm font-semibold">
                                @if($metrics['storage_mb']['limit'] === PHP_INT_MAX)
                                    무제한
                                @elseif($metrics['storage_mb']['limit'] >= 1024)
                                    {{ $metrics['storage_mb']['limit'] / 1024 }} GB
                                @else
                                    {{ $metrics['storage_mb']['limit'] }} MB
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">멤버</dt>
                            <dd class="text-sm font-semibold">
                                {{ $metrics['members']['limit'] === PHP_INT_MAX ? '무제한' : $metrics['members']['limit'] }}
                            </dd>
                        </div>
                    </dl>

                    @if($tenant->plan !== 'enterprise')
                        <div class="mt-6">
                            <a href="{{ route('tenant.subscription.plans', ['tenant' => $tenant->subdomain]) }}"
                               class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                플랜 업그레이드
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // API Calls Bar Chart
            const apiData = @json($apiDaily);
            const labels = Object.keys(apiData).map(d => {
                const date = new Date(d + 'T00:00:00');
                return date.toLocaleDateString('ko', { month: 'short', day: 'numeric' });
            });
            const values = Object.values(apiData);
            const total = values.reduce((a, b) => a + b, 0);
            document.getElementById('chart-total').textContent = `총: ${total.toLocaleString()} 호출`;

            new Chart(document.getElementById('apiChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'API 호출',
                        data: values,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { ticks: { maxTicksLimit: 10 } }
                    }
                }
            });

            // Storage Doughnut Chart
            const storageUsed = {{ $metrics['storage_mb']['current'] }};
            const storageLimit = {{ $metrics['storage_mb']['limit'] === PHP_INT_MAX ? 100 : $metrics['storage_mb']['limit'] }};
            const storageRemaining = Math.max(0, storageLimit - storageUsed);

            new Chart(document.getElementById('storageChart'), {
                type: 'doughnut',
                data: {
                    labels: ['사용 중', '사용 가능'],
                    datasets: [{
                        data: [storageUsed, storageRemaining],
                        backgroundColor: [
                            storageUsed / storageLimit > 0.8 ? 'rgb(239, 68, 68)' : 'rgb(59, 130, 246)',
                            'rgb(229, 231, 235)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom' },
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
