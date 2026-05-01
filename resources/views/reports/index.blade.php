<x-app-layout>
    <div class="px-3 pt-4 pb-24">
        <div class="flex items-center space-x-3 mb-6">
            <a href="{{ route('dashboard') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Pusat Laporan</h2>
                <p class="text-sm text-gray-500 mt-1">Cetak Dokumen Resmi Bisnis Anda</p>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Laporan Analisis Menu -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="bg-indigo-50 p-3 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-indigo-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Analisis Menu (SPK)</h3>
                        <p class="text-[10px] text-gray-500 mt-1 leading-relaxed">Matriks rekomendasi strategi menu berdasarkan volume penjualan dan profit margin.</p>
                    </div>
                </div>
                <div class="flex flex-col space-y-2 mt-4">
                    <form action="{{ route('spk.export') }}" method="GET" class="flex space-x-2">
                        <select name="days" class="flex-1 bg-gray-50 border-gray-200 text-xs font-bold rounded-xl focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="7">7 Hari Terakhir</option>
                            <option value="30" selected>30 Hari Terakhir</option>
                            <option value="90">3 Bulan Terakhir</option>
                        </select>
                        <button type="submit" class="bg-indigo-600 text-white font-bold px-4 py-2 rounded-xl text-xs hover:bg-indigo-700 shadow-md shadow-indigo-200">Unduh PDF</button>
                    </form>
                    <a href="{{ route('spk.index') }}" class="text-center bg-white border border-indigo-200 text-indigo-700 font-bold px-4 py-2 rounded-xl text-xs hover:bg-indigo-50">
                        Lihat Analisis Interaktif &rarr;
                    </a>
                </div>
            </div>

            <!-- Laporan Keuangan -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="bg-emerald-50 p-3 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-emerald-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Keuangan & Laba Rugi</h3>
                        <p class="text-[10px] text-gray-500 mt-1 leading-relaxed">Rekapitulasi total Omset, Pengeluaran (Beli bahan + Operasional), dan Laba Bersih riil.</p>
                    </div>
                </div>
                <form action="{{ route('reports.finance') }}" method="GET" class="flex space-x-2">
                    <select name="period" class="flex-1 bg-gray-50 border-gray-200 text-xs font-bold rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="today">Hari Ini</option>
                        <option value="this_week">Minggu Ini</option>
                        <option value="this_month" selected>Bulan Ini</option>
                        <option value="all">Semua Waktu</option>
                    </select>
                    <button type="submit" class="bg-emerald-500 text-white font-bold px-4 py-2 rounded-xl text-xs hover:bg-emerald-600 shadow-md shadow-emerald-200">Unduh PDF</button>
                </form>
            </div>

            <!-- Laporan Aset Inventori -->
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="bg-amber-50 p-3 rounded-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Aset Inventori Dapur</h3>
                        <p class="text-[10px] text-gray-500 mt-1 leading-relaxed">Detail sisa fisik bahan baku di gudang beserta valuasi total uang yang mengendap pada bahan mentah.</p>
                    </div>
                </div>
                <form action="{{ route('reports.inventory') }}" method="GET" class="flex space-x-2">
                    <div class="flex-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-3 py-2 rounded-xl flex items-center border border-amber-100">
                        *Laporan bersifat Real-Time
                    </div>
                    <button type="submit" class="bg-amber-500 text-white font-bold px-4 py-2 rounded-xl text-xs hover:bg-amber-600 shadow-md shadow-amber-200">Unduh PDF</button>
                </form>
            </div>

        </div>

        <!-- Tombol Input Pengeluaran Lainnya -->
        <div x-data="{ showExpenseModal: false }" class="mt-6">
            <button @click="showExpenseModal = true" class="w-full bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 font-bold py-4 px-6 rounded-2xl shadow-sm flex justify-center items-center active:scale-95 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 mr-2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Catat Pengeluaran Operasional (Gaji, Listrik, Sewa)
            </button>

            <!-- MODAL INPUT PENGELUARAN -->
            <div x-show="showExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" x-transition.opacity style="display: none;">
                <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl relative" @click.away="showExpenseModal = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-10 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-red-50 rounded-t-3xl">
                        <div>
                            <h3 class="text-lg font-extrabold text-red-900 leading-tight">Catat Pengeluaran</h3>
                            <p class="text-[10px] text-red-600 font-semibold">Tercatat di Laporan Keuangan Bulan Ini</p>
                        </div>
                        <button @click="showExpenseModal = false" class="text-red-400 hover:text-red-600 bg-white rounded-full p-1.5 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <form action="{{ route('reports.store_expense') }}" method="POST" class="p-5">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Kategori Pengeluaran</label>
                                <select name="category" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-red-500 focus:border-red-500 font-semibold text-gray-700 p-2.5">
                                    <option value="Gaji Karyawan">Gaji Karyawan</option>
                                    <option value="Sewa Tempat">Sewa Tempat</option>
                                    <option value="Tagihan Listrik/Air">Tagihan Listrik / Air / Internet</option>
                                    <option value="Peralatan Dapur">Beli Peralatan Dapur (Panci, dll)</option>
                                    <option value="Lain-Lain">Lain-lain</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal Bayar</label>
                                <input type="date" name="expense_date" required value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-red-500 focus:border-red-500 font-bold p-2.5">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Total Biaya (Rp)</label>
                                <input type="number" name="amount" required class="w-full text-base border-gray-200 bg-red-50 rounded-xl focus:ring-red-500 focus:border-red-500 font-bold p-3 text-red-700" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Catatan</label>
                                <input type="text" name="description" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-red-500 focus:border-red-500 font-semibold p-2.5" placeholder="Cth: Gaji Kasir Bulan Mei">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-3.5 rounded-xl shadow-lg shadow-red-200 active:scale-95 transition-transform">
                                SIMPAN PENGELUARAN
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mt-4 p-3 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('success') }}
            </div>
        @endif
    </div>
</x-app-layout>
