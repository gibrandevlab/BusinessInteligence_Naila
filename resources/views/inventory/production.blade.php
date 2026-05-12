<x-app-layout>
    <div x-data="productionManager()" class="pb-24">
        <!-- Header -->
        <div class="px-3 pt-4 pb-4 bg-white shadow-sm mb-4 sticky top-0 z-30 flex items-center space-x-3">
            <a href="{{ route('inventory.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Stok Siap Jual (Produksi)</h2>
                <p class="text-xs text-gray-500">Buat Stok & Potong Bahan Otomatis</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-3 mb-4 p-3 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-3 mb-4 p-3 bg-red-100 border border-red-200 text-red-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('error') }}
            </div>
        @endif

        <div class="px-3 mb-4">
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4">
                <p class="text-[10px] text-indigo-800 font-medium leading-relaxed">
                    Setiap porsi yang diproduksi akan <strong>langsung memotong stok bahan baku</strong> di dapur dan <strong>mencatat biaya operasional</strong> (Kemasan & Gas/Tenaga) ke pengeluaran.
                </p>
            </div>
        </div>

        <div class="space-y-4 px-3">
            @foreach($menus as $menu)
                @php
                    $capacity = $menu->production_capacity;
                    $capColor = $capacity > 0 ? 'text-emerald-700' : 'text-red-600';
                    $stockColor = $menu->current_stock > 0 ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-100 text-gray-500 border-gray-200';
                @endphp
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 text-sm mb-1">{{ $menu->name }}</h4>
                            <p class="text-[10px] text-gray-500">Stok Matang: <span class="font-bold {{ $menu->current_stock > 0 ? 'text-indigo-600' : 'text-gray-900' }} text-sm">{{ $menu->current_stock }} Porsi</span></p>
                            <p class="text-[10px] text-gray-500 mt-0.5">Potensi Dibuat: <span class="font-bold {{ $capColor }}">{{ $capacity }} Porsi</span> sisa</p>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2 pt-2 border-t border-gray-50">
                        <a href="{{ route('recipe.index') }}" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-600 py-2 rounded-lg text-[10px] font-bold flex items-center justify-center transition-colors border border-gray-100">
                            Cek Resep
                        </a>
                        <button @click="openProduction( {{ $menu->id }}, '{{ $menu->name }}', {{ $capacity }} )" {{ $capacity == 0 ? 'disabled' : '' }} class="flex-1 {{ $capacity > 0 ? 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }} py-2 rounded-lg text-[10px] font-bold flex items-center justify-center transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Tambah
                        </button>
                        <button @click="openOpname( {{ $menu->id }}, '{{ $menu->name }}', {{ $menu->current_stock }} )" class="flex-1 bg-amber-50 hover:bg-amber-100 text-amber-700 py-2 rounded-lg text-[10px] font-bold flex items-center justify-center transition-colors border border-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Koreksi (Cacat)
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- MODAL PRODUKSI -->
        <div x-show="showProduction" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl relative" @click.away="showProduction = false">
                <button @click="showProduction = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <h3 class="text-lg font-extrabold text-gray-900 mb-1">Catat Hasil Produksi</h3>
                <p class="text-xs text-indigo-600 font-semibold mb-4" x-text="selectedMenuName"></p>

                <form action="{{ route('inventory.produce') }}" method="POST">
                    @csrf
                    <input type="hidden" name="menu_id" x-model="selectedMenuId">
                    
                    <div class="bg-emerald-50 rounded-xl p-3 mb-4 border border-emerald-100">
                        <p class="text-[10px] text-emerald-700 font-semibold mb-1">Maksimal yang bisa dibuat saat ini:</p>
                        <p class="text-xl font-extrabold text-emerald-900"><span x-text="maxCapacity"></span> <span class="text-xs font-bold uppercase">Porsi</span></p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Jumlah Porsi yang Dibuat</label>
                            <input type="number" name="quantity" min="1" :max="maxCapacity" required class="w-full text-lg border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-black p-3 text-center text-indigo-600" placeholder="0">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-indigo-200 active:scale-95 transition-transform">Simpan & Potong Bahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

        <!-- MODAL KOREKSI (OPNAME) -->
        <div x-show="showOpname" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl relative" @click.away="showOpname = false">
                <button @click="showOpname = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <h3 class="text-lg font-extrabold text-gray-900 mb-1">Koreksi Produk Jadi</h3>
                <p class="text-xs text-amber-600 font-semibold mb-4" x-text="selectedMenuName"></p>

                <form action="{{ route('inventory.production.opname') }}" method="POST">
                    @csrf
                    <input type="hidden" name="menu_id" x-model="selectedMenuId">
                    
                    <div class="bg-amber-50 rounded-xl p-3 mb-4 border border-amber-100">
                        <p class="text-[10px] text-amber-700 font-semibold mb-1">Stok Matang di Sistem Saat Ini:</p>
                        <p class="text-xl font-extrabold text-amber-900"><span x-text="currentStock"></span> <span class="text-xs font-bold uppercase">Porsi</span></p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Stok Realita Etalase (Porsi)</label>
                            <input type="number" step="1" name="actual_stock" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5 text-red-600" placeholder="Masukkan sisa porsi yang bagus">
                            <p class="text-[10px] text-gray-400 mt-1 leading-tight">Jika ada yang basi/cacat, sistem akan mendeteksi selisihnya sebagai pengeluaran kerugian.</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Alasan / Keterangan</label>
                            <input type="text" name="reason" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-medium p-2.5" placeholder="Contoh: Basi, Jatuh, Gosong, dll">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-amber-200 active:scale-95 transition-transform">Koreksi & Hitung Kerugian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionManager', () => ({
                showProduction: false,
                showOpname: false,
                selectedMenuId: null,
                selectedMenuName: '',
                maxCapacity: 0,
                currentStock: 0,

                openProduction(id, name, capacity) {
                    if(capacity <= 0) return;
                    this.selectedMenuId = id;
                    this.selectedMenuName = name;
                    this.maxCapacity = capacity;
                    this.showProduction = true;
                    this.showOpname = false;
                },

                openOpname(id, name, stock) {
                    this.selectedMenuId = id;
                    this.selectedMenuName = name;
                    this.currentStock = stock;
                    this.showOpname = true;
                    this.showProduction = false;
                }
            }));
        });
    </script>
</x-app-layout>
