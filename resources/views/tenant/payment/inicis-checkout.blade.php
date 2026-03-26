<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">결제</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                {{-- 결제 정보 요약 --}}
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $planConfig['name'] }} 플랜</h3>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">상품명</span>
                        <span class="font-medium">{{ $paymentParams['goodname'] }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1">
                        <span class="text-gray-600">결제 금액</span>
                        <span class="text-xl font-bold text-blue-600">{{ number_format($paymentParams['price']) }}원</span>
                    </div>
                    @if(isset($isBilling) && $isBilling)
                        <p class="mt-2 text-xs text-yellow-600 bg-yellow-50 p-2 rounded">
                            정기결제: 매월 자동으로 {{ number_format($paymentParams['price']) }}원이 결제됩니다.
                        </p>
                    @endif
                </div>

                {{-- 결제 버튼 --}}
                <button id="payBtn" onclick="requestPay()"
                    class="w-full py-3 px-4 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    {{ number_format($paymentParams['price']) }}원 결제하기
                </button>

                <p class="mt-3 text-center text-xs text-gray-400">
                    KG이니시스 안전결제 (테스트 모드)
                </p>

                {{-- 이니시스 표준결제 폼 (hidden) --}}
                <form id="inicisForm" method="POST" accept-charset="UTF-8" hidden>
                    @foreach($paymentParams as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    {{-- 이니시스 INIStd JavaScript --}}
    <script src="https://stdpay.inicis.com/stdjs/INIStdPay.js" charset="UTF-8"></script>
    <script>
        function requestPay() {
            const btn = document.getElementById('payBtn');
            btn.disabled = true;
            btn.textContent = '결제창 호출 중...';

            INIStdPay.pay('inicisForm');
        }
    </script>
    @endpush
</x-app-layout>
