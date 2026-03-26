<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">파일</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'file-uploaded')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">파일이 업로드되었습니다.</div>
            @elseif (session('status') === 'file-deleted')
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">파일이 삭제되었습니다.</div>
            @endif
            @if ($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            {{-- Storage meter --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">저장소</span>
                    <span class="font-medium">
                        {{ number_format($storageUsed, 1) }} MB /
                        {{ $storageLimit === PHP_INT_MAX ? '무제한' : number_format($storageLimit) . ' MB' }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="h-2.5 rounded-full {{ $storagePercent > 80 ? 'bg-red-500' : 'bg-blue-500' }}"
                         style="width: {{ min(100, $storagePercent) }}%"></div>
                </div>
            </div>

            {{-- Upload --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">파일 업로드</h3>
                <form method="POST" action="{{ route('tenant.files.upload', ['tenant' => $tenant->subdomain]) }}" enctype="multipart/form-data" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <input type="file" name="file" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <x-primary-button>업로드</x-primary-button>
                </form>
            </div>

            {{-- File list --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">파일 ({{ $files->count() }})</h3>
                @if($files->isEmpty())
                    <p class="text-gray-500 text-sm">아직 업로드된 파일이 없습니다.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">이름</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">크기</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">작업</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($files as $file)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $file['name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ number_format($file['size'] / 1024, 1) }} KB</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('tenant.files.destroy', ['tenant' => $tenant->subdomain, 'filename' => $file['name']]) }}"
                                              onsubmit="return confirm('이 파일을 삭제하시겠습니까?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">삭제</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
