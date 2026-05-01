<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Naila ERP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50">
        
        <!-- Mobile Wrapper -->
        <div class="relative min-h-screen max-w-md mx-auto bg-white shadow-xl overflow-hidden pb-20">
            
            <!-- Top App Bar -->
            <header class="bg-indigo-600 text-white sticky top-0 z-50 px-4 py-4 shadow-md flex justify-between items-center rounded-b-2xl">
                <div>
                    @isset($header)
                        {{ $header }}
                    @else
                        <h1 class="text-xl font-bold tracking-tight">Naila ERP</h1>
                        <p class="text-xs text-indigo-200">Hi, {{ Auth::user()->name ?? 'Kasir' }}</p>
                    @endisset
                </div>
                <!-- Profile / Settings -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-400 p-2 rounded-full transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                    </button>
                </form>
            </header>

            <!-- Page Content -->
            <main class="p-4 space-y-6">
                {{ $slot }}
            </main>

            <!-- Bottom Navigation Bar -->
            <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] z-50">
                <div class="flex justify-around items-center h-16">
                    <!-- Home/Dashboard -->
                    <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="{{ request()->routeIs('dashboard') ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span class="text-[10px] font-medium mt-1">Home</span>
                    </a>

                    <!-- POS/Transaksi -->
                    <a href="{{ route('pos.index') }}" class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('pos.index') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="{{ request()->routeIs('pos.index') ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[10px] font-medium mt-1">Kasir</span>
                    </a>

                    <!-- Menu Laporan (FAB Style) -->
                    <div class="relative w-full h-full flex justify-center -top-5">
                        <a href="{{ route('reports.index') }}" class="absolute {{ request()->routeIs('reports.index') ? 'bg-gradient-to-tr from-rose-600 to-orange-600' : 'bg-gradient-to-tr from-indigo-600 to-purple-600' }} text-white rounded-full p-4 shadow-lg shadow-indigo-300 flex items-center justify-center ring-4 ring-white transition-transform active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </a>
                        <span class="absolute bottom-1 text-[10px] font-bold {{ request()->routeIs('reports.index') ? 'text-rose-600' : 'text-indigo-600' }}">Laporan</span>
                    </div>

                    <!-- Inventory -->
                    <a href="{{ route('inventory.index') }}" class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('inventory.index') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="{{ request()->routeIs('inventory.index') ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                        <span class="text-[10px] font-medium mt-1">Stok</span>
                    </a>

                    <!-- Resep -->
                    <a href="{{ route('recipe.index') }}" class="flex flex-col items-center justify-center w-full h-full {{ request()->routeIs('recipe.index') ? 'text-indigo-600' : 'text-gray-400 hover:text-indigo-500' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="{{ request()->routeIs('recipe.index') ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span class="text-[10px] font-medium mt-1">Resep</span>
                    </a>
                </div>
            </nav>

        </div>
    </body>
</html>
