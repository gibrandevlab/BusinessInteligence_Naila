<x-app-layout>
    <div x-data="recipeEditManager({{ $ingredients->toJson() }}, {{ json_encode($recipe->items) }})" class="pb-24">
        <div class="px-3 pt-4 pb-4 flex justify-between items-center sticky top-0 z-30 bg-white/90 backdrop-blur-md mb-4 shadow-sm border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <a href="{{ route('recipe.index') }}" class="bg-gray-100 p-2 rounded-full text-gray-500 hover:bg-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                </a>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Edit Resep</h2>
                    <p class="text-xs text-gray-500 mt-1">{{ $recipe->name }}</p>
                </div>
            </div>
        </div>

        <div class="px-3">
            <form id="recipe-edit-form" action="{{ route('recipe.update', $recipe) }}" method="POST" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                @csrf
                @method('PUT')
                <input type="hidden" name="ingredients" :value="JSON.stringify(selectedIngredients)">
                
                <div class="p-4">
                    <!-- 1. Identitas Menu -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">1. Identitas Produk</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Menu</label>
                                <input type="text" name="name" required value="{{ $recipe->menuItem ? $recipe->menuItem->name : $recipe->name }}" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Kategori</label>
                                <select name="category" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-700 p-2.5">
                                    <option value="Makanan" {{ ($recipe->menuItem && $recipe->menuItem->category == 'Makanan') || ($recipe->menuItem && $recipe->menuItem->category == 'Makanan Utama') ? 'selected' : '' }}>Makanan</option>
                                    <option value="Minuman" {{ $recipe->menuItem && $recipe->menuItem->category == 'Minuman' ? 'selected' : '' }}>Minuman</option>
                                    <option value="Snack" {{ $recipe->menuItem && $recipe->menuItem->category == 'Snack' || ($recipe->menuItem && $recipe->menuItem->category == 'Cemilan') ? 'selected' : '' }}>Snack / Cemilan</option>
                                    <option value="Lauk" {{ $recipe->menuItem && $recipe->menuItem->category == 'Lauk' ? 'selected' : '' }}>Lauk</option>
                                    <option value="Lainnya" {{ $recipe->menuItem && $recipe->menuItem->category == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Racikan Resep (Bahan Baku) -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3 flex justify-between items-center">
                            2. Komposisi Bahan
                            <button type="button" @click="addIngredientRow" class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md hover:bg-indigo-200">+ Tambah Bahan</button>
                        </h4>
                        
                        <div class="space-y-3">
                            <template x-for="(ing, index) in selectedIngredients" :key="index">
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 relative">
                                    <button type="button" @click="removeIngredientRow(index)" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full w-5 h-5 flex items-center justify-center font-bold text-xs hover:bg-red-200">&times;</button>
                                    
                                    <div class="mb-2">
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">Pilih Bahan (Dari Gudang)</label>
                                        <select x-model="ing.ingredient_id" @change="updateIngredientDetails(index)" class="w-full text-xs border-gray-200 bg-white rounded-lg p-2 font-semibold">
                                            <option value="">-- Buat Bahan Baru --</option>
                                            <template x-for="dbIng in dbIngredients" :key="dbIng.id">
                                                <option :value="dbIng.id" x-text="dbIng.name + ' (' + dbIng.unit + ')'"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Jika Bahan Baru -->
                                    <div x-show="!ing.ingredient_id" class="grid grid-cols-2 gap-2 mb-2 p-2 bg-white rounded-lg border border-amber-100">
                                        <div class="col-span-2">
                                            <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Nama Bahan Baru</label>
                                            <input type="text" x-model="ing.name" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: Keju Mozarella">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Satuan</label>
                                            <input type="text" x-model="ing.unit" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: Gram">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Harga / Satuan</label>
                                            <input type="number" x-model="ing.cost_per_unit" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: 150">
                                        </div>
                                    </div>

                                    <!-- Kuantitas Resep -->
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Takaran Per 1 Porsi <span x-text="ing.unit ? '('+ing.unit+')' : ''"></span></label>
                                        <input type="number" step="0.001" x-model="ing.quantity" class="w-full text-xs border-gray-300 bg-white rounded-lg p-2 font-bold text-indigo-600" placeholder="Contoh: 150">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 3. Biaya Operasional -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">3. Biaya Lain Per Porsi (Rp)</h4>
                        <div class="flex space-x-3">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Kemasan (Dus/Plastik)</label>
                                <input type="number" name="packaging_cost" required value="{{ $recipe->packaging_cost }}" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-bold">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Overhead (Gas/Listrik)</label>
                                <input type="number" name="overhead_cost" required value="{{ $recipe->overhead_cost }}" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Target Harga Jual -->
                    <div class="mb-5">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">4. Target Harga Jual (Rp)</h4>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Eceran</label>
                                <input type="number" name="price_eceran" required value="{{ $recipe->menuItem ? $recipe->menuItem->price_eceran : 0 }}" class="flex-1 text-sm border-gray-200 bg-emerald-50 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-bold p-2 text-emerald-700">
                            </div>
                            <div class="flex items-center space-x-3">
                                <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Reseller</label>
                                <input type="number" name="price_reseller" required value="{{ $recipe->menuItem ? $recipe->menuItem->price_reseller : 0 }}" class="flex-1 text-sm border-gray-200 bg-blue-50 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold p-2 text-blue-700">
                            </div>
                            <div class="flex items-center space-x-3">
                                <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Agen</label>
                                <input type="number" name="price_agen" required value="{{ $recipe->menuItem ? $recipe->menuItem->price_agen : 0 }}" class="flex-1 text-sm border-gray-200 bg-amber-50 rounded-xl focus:ring-amber-500 focus:border-amber-500 font-bold p-2 text-amber-700">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50 rounded-b-3xl shrink-0">
                    <button type="button" @click="submitForm" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3.5 rounded-xl shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                        SIMPAN PERUBAHAN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('recipeEditManager', (ingredientsData, existingItems) => ({
                dbIngredients: ingredientsData,
                selectedIngredients: [],

                init() {
                    // Populate existing ingredients
                    if (existingItems && existingItems.length > 0) {
                        this.selectedIngredients = existingItems.map(item => {
                            const dbIng = this.dbIngredients.find(i => i.id == item.ingredient_id);
                            return {
                                ingredient_id: item.ingredient_id,
                                name: dbIng ? dbIng.name : '',
                                unit: dbIng ? dbIng.unit : '',
                                cost_per_unit: dbIng ? dbIng.cost_per_unit : 0,
                                quantity: parseFloat(item.quantity)
                            };
                        });
                    } else {
                        this.addIngredientRow();
                    }
                },

                addIngredientRow() {
                    this.selectedIngredients.push({
                        ingredient_id: '',
                        name: '',
                        unit: '',
                        cost_per_unit: 0,
                        quantity: 1
                    });
                },

                removeIngredientRow(index) {
                    this.selectedIngredients.splice(index, 1);
                },

                updateIngredientDetails(index) {
                    const row = this.selectedIngredients[index];
                    if (row.ingredient_id) {
                        const dbIng = this.dbIngredients.find(i => i.id == row.ingredient_id);
                        if (dbIng) {
                            row.name = dbIng.name;
                            row.unit = dbIng.unit;
                            row.cost_per_unit = dbIng.cost_per_unit;
                        }
                    } else {
                        row.name = '';
                        row.unit = '';
                        row.cost_per_unit = 0;
                    }
                },

                submitForm() {
                    if(this.selectedIngredients.length === 0) {
                        alert('Resep harus memiliki minimal 1 bahan baku!');
                        return;
                    }
                    document.getElementById('recipe-edit-form').submit();
                }
            }));
        });
    </script>
</x-app-layout>
