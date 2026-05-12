<x-app-layout>
    <div class="pb-24">
        <!-- Header -->
        <div class="px-3 pt-4 pb-4 bg-white/90 backdrop-blur-md sticky top-0 z-30 mb-4 shadow-sm border-b border-gray-100 flex justify-between items-end">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Cek Menu Paling Untung</h2>
                <p class="text-xs text-gray-500 mt-1">Sistem Cerdas Analisis Menu</p>
            </div>
            <div class="bg-indigo-50 p-2 rounded-xl border border-indigo-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-indigo-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h3.375c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125h-3.375A1.125 1.125 0 0110.5 19.125v-1.5c0-.621.504-1.125 1.125-1.125z" />
                </svg>
            </div>
        </div>

        <div class="px-3">
            <!-- Filter Periode -->
            <div class="mb-6">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 px-1">Periode Analisis Penjualan</p>
                <form method="GET" action="{{ route('spk.index') }}" class="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide">
                    <button type="submit" name="days" value="7" class="shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all {{ $days == 7 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">7 Hari</button>
                    <button type="submit" name="days" value="30" class="shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all {{ $days == 30 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">1 Bulan</button>
                    <button type="submit" name="days" value="90" class="shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all {{ $days == 90 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">3 Bulan</button>
                    <button type="submit" name="days" value="365" class="shrink-0 px-4 py-2 rounded-full text-xs font-bold transition-all {{ $days == 365 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">1 Tahun</button>
                </form>
            </div>

            @if($menus->isEmpty())
                <div class="bg-amber-50 rounded-3xl p-6 border border-amber-100 text-center shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-amber-500 mx-auto mb-3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-amber-800 font-extrabold text-lg">Belum Ada Transaksi</h3>
                    <p class="text-amber-600 text-xs mt-1 leading-relaxed">Belum ada data penjualan pada periode {{ $days }} hari terakhir. Jual menu di Kasir terlebih dahulu agar sistem bisa menganalisis.</p>
                </div>
            @else
                <!-- Penjelasan Singkat & Rumus Perhitungan -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 mb-6">
                    <h3 class="text-sm font-bold text-indigo-900 mb-1">Bagaimana sistem ini bekerja?</h3>
                    <p class="text-[10px] text-indigo-700 leading-relaxed mb-3">
                        Sistem membandingkan menu Anda menggunakan <strong>Metode Menu Engineering (Matriks BCG)</strong> berdasarkan 2 hal utama: <strong>Tingkat Popularitas (Laris)</strong> dan <strong>Keuntungan / Margin (Cuan)</strong>.
                    </p>

                    <details class="group bg-white rounded-xl border border-indigo-100 overflow-hidden shadow-sm">
                        <summary class="text-[11px] font-bold text-indigo-800 cursor-pointer p-3 bg-indigo-100/50 hover:bg-indigo-100 flex justify-between items-center transition-colors">
                            <span class="flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Lihat Rumus Perhitungan
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-indigo-600 transition-transform duration-200 group-open:rotate-180">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </summary>
                        <div class="p-4 space-y-4 text-[10px] text-gray-700 bg-white">
                            <!-- Rata-rata Penjualan -->
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1 border-b border-gray-100 pb-1">1. Batas Popularitas (Menu Mix)</h4>
                                <p class="mb-1 text-gray-500">Menentukan standar batas persentase penjualan. Menu disebut "Laris" jika penjualannya di atas batas ini.</p>
                                <div class="bg-indigo-50/50 p-2.5 rounded-lg font-mono text-indigo-700 border border-indigo-100 text-[10px]">
                                    Batas Laris = (1 ÷ Jumlah Seluruh Menu) × 70% × 100
                                </div>
                            </div>

                            <!-- Rata-rata Keuntungan -->
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1 border-b border-gray-100 pb-1">2. Batas Keuntungan (Contribution Margin)</h4>
                                <p class="mb-1 text-gray-500">Menentukan rata-rata laba kotor per porsi dari seluruh menu yang terjual.</p>
                                <div class="bg-indigo-50/50 p-2.5 rounded-lg font-mono text-indigo-700 border border-indigo-100 text-[10px]">
                                    Batas Keuntungan = Total Seluruh Keuntungan ÷ Total Porsi Terjual
                                </div>
                            </div>

                            <!-- Matriks BCG -->
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1.5 border-b border-gray-100 pb-1">3. Pengelompokan Kategori</h4>
                                <div class="grid grid-cols-1 gap-2">
                                    <div class="flex items-start gap-2 bg-emerald-50 p-2 rounded-lg border border-emerald-100">
                                        <span class="text-lg leading-none mt-0.5">⭐</span>
                                        <div>
                                            <strong class="text-emerald-700 block">Star (Sangat Laku & Untung Besar)</strong>
                                            <span class="text-emerald-600/80">Penjualan > Batas Laris <b>DAN</b> Keuntungan > Batas Keuntungan</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 bg-blue-50 p-2 rounded-lg border border-blue-100">
                                        <span class="text-lg leading-none mt-0.5">🐴</span>
                                        <div>
                                            <strong class="text-blue-700 block">Plowhorse (Sangat Laku, Untung Tipis)</strong>
                                            <span class="text-blue-600/80">Penjualan > Batas Laris <b>TAPI</b> Keuntungan < Batas Keuntungan</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 bg-amber-50 p-2 rounded-lg border border-amber-100">
                                        <span class="text-lg leading-none mt-0.5">🧩</span>
                                        <div>
                                            <strong class="text-amber-700 block">Puzzle (Kurang Laku, Untung Besar)</strong>
                                            <span class="text-amber-600/80">Penjualan < Batas Laris <b>TAPI</b> Keuntungan > Batas Keuntungan</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-2 bg-red-50 p-2 rounded-lg border border-red-100">
                                        <span class="text-lg leading-none mt-0.5">🐶</span>
                                        <div>
                                            <strong class="text-red-700 block">Dog (Kurang Laku & Untung Tipis)</strong>
                                            <span class="text-red-600/80">Penjualan < Batas Laris <b>DAN</b> Keuntungan < Batas Keuntungan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Benchmark Info -->
                <div class="grid grid-cols-2 gap-3 mb-8">
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-24 h-24 text-blue-600"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                        </div>
                        <p class="text-gray-400 text-[9px] font-bold uppercase tracking-wider mb-0.5">Rata-Rata Terjual</p>
                        <h3 class="text-lg font-black text-gray-900">{{ number_format($avg_mm, 2) }}% <span class="text-[10px] text-gray-500 font-medium">dari total</span></h3>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-24 h-24 text-emerald-600"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="text-gray-400 text-[9px] font-bold uppercase tracking-wider mb-0.5">Rata-Rata Keuntungan</p>
                        <h3 class="text-lg font-black text-gray-900">Rp {{ number_format($avg_cm, 0, ',', '.') }} <span class="text-[10px] text-gray-500 font-medium">/ porsi</span></h3>
                    </div>
                </div>

                <!-- Matriks BCG Results -->
                <div class="flex justify-between items-end mb-4 px-1">
                    <h3 class="text-lg font-extrabold text-gray-900 tracking-tight">Hasil Rekomendasi Menu</h3>
                </div>
                
                <div class="space-y-4">
                    @foreach($menus as $menu)
                        @php
                            $catBg = ''; $catText = ''; $catIcon = ''; $catLabel = ''; $catBorder = '';
                            if($menu->category == 'Star') {
                                $catBg = 'bg-emerald-50'; $catText = 'text-emerald-700'; $catBorder = 'border-emerald-200';
                                $catIcon = '⭐'; $catLabel = 'Sangat laku dan keuntungan besar';
                            } elseif($menu->category == 'Plowhorse') {
                                $catBg = 'bg-blue-50'; $catText = 'text-blue-700'; $catBorder = 'border-blue-200';
                                $catIcon = '🐴'; $catLabel = 'Sangat laku, tapi keuntungan sedikit';
                            } elseif($menu->category == 'Puzzle') {
                                $catBg = 'bg-amber-50'; $catText = 'text-amber-700'; $catBorder = 'border-amber-200';
                                $catIcon = '🧩'; $catLabel = 'Kurang laku, tapi keuntungan besar';
                            } else {
                                $catBg = 'bg-red-50'; $catText = 'text-red-700'; $catBorder = 'border-red-200';
                                $catIcon = '🐶'; $catLabel = 'Kurang laku dan keuntungan sedikit';
                            }
                        @endphp

                        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 relative">
                            <!-- Kategori Label -->
                            <div class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black tracking-wide mb-3 {{ $catBg }} {{ $catText }} border {{ $catBorder }}">
                                <span class="mr-1.5">{{ $catIcon }}</span> {{ $catLabel }}
                            </div>

                            <h4 class="font-black text-gray-900 text-lg mb-2 leading-tight">{{ $menu->name }}</h4>
                            
                            <!-- Stats Grid -->
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100 flex flex-col justify-center">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Penjualan</span>
                                    <span class="text-sm font-bold {{ $menu->mm_percent >= $avg_mm ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ number_format($menu->mm_percent, 1) }}% 
                                        <span class="text-[10px] font-medium text-gray-500">({{ $menu->mm_percent >= $avg_mm ? 'Tinggi' : 'Rendah' }})</span>
                                    </span>
                                </div>
                                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100 flex flex-col justify-center">
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Untung / Porsi</span>
                                    <span class="text-sm font-bold {{ $menu->cm_per_item >= $avg_cm ? 'text-emerald-600' : 'text-red-500' }}">
                                        Rp{{ number_format($menu->cm_per_item, 0, ',', '.') }}
                                        <span class="text-[10px] font-medium text-gray-500">({{ $menu->cm_per_item >= $avg_cm ? 'Tinggi' : 'Rendah' }})</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Saran / Action -->
                            <div class="bg-gray-900 rounded-xl p-3 shadow-inner">
                                <div class="flex items-start space-x-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.829 1.508-2.336 1.145-.683 1.942-1.927 1.942-3.373a4.5 4.5 0 00-9 0c0 1.446.797 2.69 1.942 3.373.85.507 1.508 1.353 1.508 2.336v.192" /></svg>
                                    <div>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-0.5">Saran Aksi Bisnis:</span>
                                        <p class="text-xs font-semibold text-white leading-relaxed">{{ $menu->action }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                

            @endif
        </div>
    </div>
</x-app-layout>
