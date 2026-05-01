<x-app-layout>
    <div x-data="materialsManager()" class="pb-24">
        <!-- Header -->
        <div class="px-3 pt-4 pb-4 bg-white shadow-sm mb-4 sticky top-0 z-30 flex items-center space-x-3">
            <a href="{{ route('inventory.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Gudang Bahan Mentah</h2>
                <p class="text-xs text-gray-500">Pembaruan & Koreksi Fisik</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-3 mb-4 p-3 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-3 px-3">
            @foreach($ingredients as $item)
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm">{{ $item->name }}</h4>
                            <p class="text-[10px] text-gray-500 mt-1">Harga Satuan Terakhir: Rp{{ number_format($item->cost_per_unit, 0, ',', '.') }} / {{ $item->unit }}</p>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-extrabold {{ $item->current_stock <= $item->min_stock ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-100' }}">
                                {{ $item->current_stock }} <span class="text-[10px] font-medium ml-1">{{ $item->unit }}</span>
                            </span>
                            @if($item->current_stock <= $item->min_stock)
                                <p class="text-[10px] font-bold text-red-500 mt-1 uppercase animate-bounce">Stok Menipis!</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex space-x-2 border-t border-gray-50 pt-3 mt-1">
                        <button @click="openPurchase( {{ $item->id }}, '{{ $item->name }}', '{{ $item->unit }}' )" class="flex-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 py-1.5 rounded-lg text-[10px] font-bold flex items-center justify-center transition-colors border border-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Beli Bahan
                        </button>
                        <button @click="openOpname( {{ $item->id }}, '{{ $item->name }}', {{ $item->current_stock }}, '{{ $item->unit }}' )" class="flex-1 bg-amber-50 hover:bg-amber-100 text-amber-700 py-1.5 rounded-lg text-[10px] font-bold flex items-center justify-center transition-colors border border-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            Koreksi Fisik (Rusak/Susut)
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- MODAL PEMBELIAN -->
        <div x-show="showPurchase" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl relative" @click.away="showPurchase = false">
                <button @click="showPurchase = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <h3 class="text-lg font-extrabold text-gray-900 mb-1">Catat Pembelian Bahan</h3>
                <p class="text-xs text-gray-500 mb-4" x-text="selectedItemName"></p>

                <form action="{{ route('inventory.purchase') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ingredient_id" x-model="selectedItemId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Jumlah Dibeli (<span x-text="selectedItemUnit"></span>)</label>
                            <input type="number" step="0.01" name="quantity" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5" placeholder="Contoh: 5000">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Bayar Keseluruhan (Rp)</label>
                            <input type="number" name="total_price" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5" placeholder="Contoh: 150000">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Supplier (Opsional)</label>
                            <select name="supplier_id" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-700 p-2.5">
                                <option value="">Tanpa Supplier / Pasar Bebas</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-emerald-200 active:scale-95 transition-transform">Simpan Pembelian</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL KOREKSI (OPNAME) -->
        <div x-show="showOpname" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-5 shadow-2xl relative" @click.away="showOpname = false">
                <button @click="showOpname = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <h3 class="text-lg font-extrabold text-gray-900 mb-1">Koreksi Fisik Bahan</h3>
                <p class="text-xs text-gray-500 mb-4" x-text="selectedItemName"></p>

                <form action="{{ route('inventory.opname') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ingredient_id" x-model="selectedItemId">
                    
                    <div class="bg-amber-50 rounded-xl p-3 mb-4 border border-amber-100">
                        <p class="text-[10px] text-amber-700 font-semibold mb-1">Stok di Sistem Saat Ini:</p>
                        <p class="text-xl font-extrabold text-amber-900"><span x-text="currentStock"></span> <span class="text-xs font-bold uppercase" x-text="selectedItemUnit"></span></p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Stok Realita Dapur (<span x-text="selectedItemUnit"></span>)</label>
                            <input type="number" step="0.01" name="actual_stock" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5 text-red-600" placeholder="Masukkan angka riil saat ini">
                            <p class="text-[10px] text-gray-400 mt-1 leading-tight">Jika lebih kecil, sistem otomatis mencatat kerugian pengeluaran (Bahan Rusak/Susut).</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Alasan / Keterangan</label>
                            <input type="text" name="reason" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-medium p-2.5" placeholder="Contoh: Tomat busuk, ayam jatuh, dsb">
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl shadow-lg shadow-amber-200 active:scale-95 transition-transform">Sesuaikan Stok & Catat Laporan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('materialsManager', () => ({
                showPurchase: false,
                showOpname: false,
                
                selectedItemId: null,
                selectedItemName: '',
                selectedItemUnit: '',
                currentStock: 0,

                openPurchase(id, name, unit) {
                    this.selectedItemId = id;
                    this.selectedItemName = name;
                    this.selectedItemUnit = unit;
                    this.showPurchase = true;
                    this.showOpname = false;
                },

                openOpname(id, name, stock, unit) {
                    this.selectedItemId = id;
                    this.selectedItemName = name;
                    this.currentStock = stock;
                    this.selectedItemUnit = unit;
                    this.showOpname = true;
                    this.showPurchase = false;
                }
            }));
        });
    </script>
</x-app-layout>
