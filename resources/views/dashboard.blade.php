<x-app-layout>
    <div class="px-3 pt-4 pb-24">
        <!-- Header & Filter -->
        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Ringkasan</h2>
                <p class="text-sm text-gray-500 mt-1">Pantau performa bisnis Anda</p>
            </div>
            
            <!-- Filter Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center space-x-1 bg-white border border-gray-200 shadow-sm px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50">
                    <span>
                        @if($kpi['current_period'] == 'today') Hari Ini
                        @elseif($kpi['current_period'] == 'yesterday') Kemarin
                        @elseif($kpi['current_period'] == 'this_week') Minggu Ini
                        @elseif($kpi['current_period'] == 'this_month') Bulan Ini
                        @endif
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                
                <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                    <a href="?period=today" class="block px-4 py-2 text-xs font-semibold {{ $kpi['current_period'] == 'today' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Hari Ini</a>
                    <a href="?period=yesterday" class="block px-4 py-2 text-xs font-semibold {{ $kpi['current_period'] == 'yesterday' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Kemarin</a>
                    <a href="?period=this_week" class="block px-4 py-2 text-xs font-semibold {{ $kpi['current_period'] == 'this_week' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Minggu Ini</a>
                    <a href="?period=this_month" class="block px-4 py-2 text-xs font-semibold {{ $kpi['current_period'] == 'this_month' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Bulan Ini</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-6">
            <!-- Omset (Uang Masuk) -->
            <div class="bg-gradient-to-br from-indigo-500 to-blue-600 rounded-3xl p-5 shadow-lg shadow-blue-200 text-white relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-24 h-24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <h3 class="text-xs font-medium text-indigo-100 mb-1">Uang Masuk</h3>
                <p class="text-xl font-extrabold tracking-tight">Rp{{ number_format($kpi['revenue'], 0, ',', '.') }}</p>
            </div>

            <!-- Total Pengeluaran -->
            <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-5 shadow-lg shadow-red-200 text-white relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-24 h-24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xs font-medium text-rose-100 mb-1">Pengeluaran Riil</h3>
                <p class="text-xl font-extrabold tracking-tight">Rp{{ number_format($kpi['pengeluaran'], 0, ',', '.') }}</p>
            </div>

            <!-- Keuntungan Bersih -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-5 shadow-lg shadow-emerald-200 text-white col-span-2 relative overflow-hidden">
                <div class="absolute -right-2 -bottom-6 opacity-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <h3 class="text-xs font-medium text-emerald-100 mb-1">Untung (Laba Kotor Penjualan)</h3>
                <p class="text-3xl font-extrabold tracking-tight">Rp{{ number_format($kpi['profit'], 0, ',', '.') }}</p>
                <div class="mt-4 inline-flex items-center px-2 py-1 bg-white/20 rounded-lg text-xs font-medium backdrop-blur-sm">
                    Rata-Rata Modal Bahan: <span class="ml-1 font-bold">{{ $kpi['fc_percent'] }}%</span>
                </div>
            </div>
        </div>

        <!-- Menu Favorit -->
        <h3 class="font-bold text-gray-900 mb-3 px-1 text-sm uppercase tracking-wide">Menu Dijual</h3>
        <div class="space-y-3">
            @foreach($menus as $menu)
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm leading-tight">{{ $menu->name }}</h4>
                            <p class="text-[10px] font-semibold text-emerald-600 mt-0.5">Ecer: Rp {{ number_format($menu->price_eceran, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Modal Asli</p>
                        <p class="text-sm font-black text-gray-800">Rp {{ number_format($menu->hpp, 0, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-4 grid grid-cols-2 gap-3">
            <a href="{{ route('spk.index') }}" class="inline-flex justify-center items-center bg-white border border-gray-200 text-xs font-bold text-gray-700 py-3 rounded-xl shadow-sm hover:bg-gray-50 active:scale-95 transition-all">
                Analisis SPK Menu
            </a>
            <a href="{{ route('reports.index') }}" class="inline-flex justify-center items-center bg-gray-900 text-xs font-bold text-white py-3 rounded-xl shadow-sm hover:bg-gray-800 active:scale-95 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                Pusat Laporan PDF
            </a>
        </div>
    </div>
</x-app-layout>
