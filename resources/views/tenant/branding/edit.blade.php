<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">브랜딩 & 화이트 라벨</h2>
    </x-slot>

    <div class="py-12" x-data="{
        primaryColor: '{{ $tenant->primary_color }}',
        secondaryColor: '{{ $tenant->secondary_color }}',
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status') === 'branding-updated')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">브랜딩이 업데이트되었습니다.</div>
            @elseif(session('status') === 'logo-removed')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">로고가 제거되었습니다.</div>
            @endif
            @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Form --}}
                <div class="lg:col-span-2 space-y-6">
                    <form method="POST" action="{{ route('tenant.branding.update', ['tenant' => $tenant->subdomain]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Logo --}}
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">로고</h3>
                            <div class="flex items-center gap-6">
                                <div class="shrink-0">
                                    @if($tenant->logo_path)
                                        <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="현재 로고" class="h-16 w-auto rounded border border-gray-200">
                                    @else
                                        <div class="h-16 w-16 rounded border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs">
                                            로고 없음
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.svg"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-xs text-gray-400">JPG, PNG 또는 SVG. 최대 2MB.</p>
                                </div>
                                @if($tenant->logo_path)
                                    <a href="{{ route('tenant.branding.removeLogo', ['tenant' => $tenant->subdomain]) }}"
                                       onclick="event.preventDefault(); if(confirm('로고를 제거하시겠습니까?')) document.getElementById('remove-logo-form').submit();"
                                       class="text-sm text-red-600 hover:text-red-800">제거</a>
                                @endif
                            </div>
                        </div>

                        {{-- Colors --}}
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">브랜드 색상</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="primary_color" value="기본 색상" />
                                    <div class="mt-1 flex items-center gap-3">
                                        <input type="color" id="primary_color" name="primary_color"
                                               x-model="primaryColor"
                                               class="h-10 w-14 rounded border border-gray-300 cursor-pointer">
                                        <input type="text" x-model="primaryColor" readonly
                                               class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-600">
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="secondary_color" value="보조 색상" />
                                    <div class="mt-1 flex items-center gap-3">
                                        <input type="color" id="secondary_color" name="secondary_color"
                                               x-model="secondaryColor"
                                               class="h-10 w-14 rounded border border-gray-300 cursor-pointer">
                                        <input type="text" x-model="secondaryColor" readonly
                                               class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-600">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">이메일 설정</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="email_from_name" value="발신자 이름" />
                                    <x-text-input id="email_from_name" name="email_from_name" type="text" class="mt-1 block w-full"
                                        :value="old('email_from_name', $tenant->email_from_name)" :placeholder="$tenant->name" />
                                </div>
                                <div>
                                    <x-input-label for="email_from_address" value="발신자 주소" />
                                    <x-text-input id="email_from_address" name="email_from_address" type="email" class="mt-1 block w-full"
                                        :value="old('email_from_address', $tenant->email_from_address)" placeholder="noreply@yourdomain.com" />
                                </div>
                            </div>
                        </div>

                        {{-- Custom Domain --}}
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">커스텀 도메인</h3>
                            <div>
                                <x-input-label for="custom_domain" value="도메인" />
                                <x-text-input id="custom_domain" name="custom_domain" type="text" class="mt-1 block w-full"
                                    :value="old('custom_domain', $tenant->custom_domain)" placeholder="app.yourdomain.com" />
                                <p class="mt-1 text-xs text-gray-400">비워두면 {{ $tenant->subdomain }}.{{ config('app.base_domain') }}을 사용합니다</p>
                            </div>

                            @if($tenant->custom_domain)
                                <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                                    <p class="font-medium text-blue-800 mb-2">DNS 설정</p>
                                    <p class="text-blue-700">다음으로 CNAME 레코드를 추가하세요:</p>
                                    <code class="block mt-1 p-2 bg-white rounded border border-blue-200 text-blue-900 font-mono text-xs">
                                        {{ $tenant->custom_domain }} CNAME {{ $tenant->subdomain }}.{{ config('app.base_domain') }}
                                    </code>
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>브랜딩 저장</x-primary-button>
                        </div>
                    </form>

                    @if($tenant->logo_path)
                        <form id="remove-logo-form" method="POST" action="{{ route('tenant.branding.removeLogo', ['tenant' => $tenant->subdomain]) }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    @endif
                </div>

                {{-- Live Preview --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-4">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">실시간 미리보기</h3>

                        {{-- Nav Preview --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-4 py-3 flex items-center gap-3" :style="'border-bottom: 3px solid ' + primaryColor">
                                @if($tenant->logo_path)
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" class="h-6 w-auto">
                                @else
                                    <span class="font-bold text-sm" :style="'color:' + primaryColor">{{ $tenant->name }}</span>
                                @endif
                                <div class="flex gap-4 text-xs text-gray-500">
                                    <span>대시보드</span><span>멤버</span><span>사용량</span>
                                </div>
                            </div>
                        </div>

                        {{-- Button Preview --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
                            <p class="text-xs text-gray-500 uppercase font-medium">버튼</p>
                            <button class="w-full py-2 px-4 rounded-md text-sm font-semibold text-white"
                                    :style="'background-color:' + primaryColor">
                                기본 버튼
                            </button>
                            <button class="w-full py-2 px-4 rounded-md text-sm font-semibold text-white"
                                    :style="'background-color:' + secondaryColor">
                                보조 버튼
                            </button>
                        </div>

                        {{-- Email Preview --}}
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-2">
                            <p class="text-xs text-gray-500 uppercase font-medium">이메일 미리보기</p>
                            <div :style="'border-bottom: 3px solid ' + primaryColor" class="pb-2 mb-2">
                                <span class="font-bold text-sm" :style="'color:' + primaryColor">{{ $tenant->name }}</span>
                            </div>
                            <p class="text-xs text-gray-700">초대되었습니다...</p>
                            <button class="py-1.5 px-3 rounded text-xs font-semibold text-white"
                                    :style="'background-color:' + primaryColor">
                                초대 수락
                            </button>
                        </div>

                        {{-- Reset --}}
                        <button @click="primaryColor = '#3B82F6'; secondaryColor = '#10B981'"
                                class="text-sm text-gray-500 hover:text-gray-700 underline">
                            기본값으로 초기화
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
