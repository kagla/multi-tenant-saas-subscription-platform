<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside class="w-64 bg-gray-900 text-white flex flex-col shrink-0">
            <div class="p-4 border-b border-gray-700">
                <h1 class="text-lg font-bold">Super Admin</h1>
                <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
            </div>
            <nav class="flex-1 py-4 space-y-1">
                <a href="{{ url('/admin') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm {{ request()->is('admin') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                    Dashboard
                </a>
                <a href="{{ url('/admin/tenants') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm {{ request()->is('admin/tenants*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Tenants
                </a>
                <a href="{{ url('/admin/users') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm {{ request()->is('admin/users*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                    Users
                </a>
                <a href="{{ url('/admin/revenue') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm {{ request()->is('admin/revenue*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Revenue
                </a>
            </nav>
            <div class="p-4 border-t border-gray-700">
                @if(session('impersonating_from'))
                    <a href="{{ url('/admin/stop-impersonating') }}" class="block w-full text-center py-2 px-3 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-500 mb-2">
                        Stop Impersonating
                    </a>
                @endif
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button class="block w-full text-center py-2 px-3 bg-gray-700 text-gray-300 text-sm rounded hover:bg-gray-600">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0">
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
            </header>
            <main class="flex-1 p-6 overflow-auto">
                @if(session('status'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                        {{ session('status') === 'tenant-suspended' ? 'Tenant suspended successfully.' : (session('status') === 'tenant-activated' ? 'Tenant activated successfully.' : session('status')) }}
                    </div>
                @endif
                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
