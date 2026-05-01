<x-app-layout>
    <div x-data="posCart({{ $menus->toJson() }})" class="pb-24">
        
        <!-- Header & Config -->
        <div class="px-3 pt-3 pb-4 bg-white shadow-sm mb-4">
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Kasir (POS)</h2>
            <p class="text-xs text-gray-500 mt-1 mb-4">Pilih Tipe Pembeli & Metode Pembayaran</p>

            <div class="flex space-x-3 mb-3">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status Pembeli</label>
                    <select x-model="buyerType" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-700 p-2.5">
                        <option value="Eceran">Pembeli Biasa (Eceran)</option>
                        <option value="Reseller">Reseller (Harga Diskon)</option>
                        <option value="Agen">Agen (Harga Grosir)</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cara Bayar</label>
                    <select x-model="paymentMethod" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-700 p-2.5">
                        <option value="Tunai">Uang Pas / Tunai</option>
                        <option value="Transfer">Transfer Bank / QRIS</option>
                    </select>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="mx-3 mb-4 p-3 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('success') }}
            </div>
        @endif

        <!-- Menu Grid -->
        <div class="grid grid-cols-2 gap-3 px-3">
            <template x-for="menu in menus" :key="menu.id">
                <div @click="addToCart(menu)" class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-col items-center text-center cursor-pointer active:scale-95 transition-transform hover:shadow-md">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mb-3 relative">
                        <!-- Badge Qty -->
                        <div x-show="getCartItemQty(menu.id) > 0" x-text="getCartItemQty(menu.id)" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-sm"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a2.25 2.25 0 002.25-2.25v-.75a2.25 2.25 0 00-2.25-2.25h-3a.75.75 0 01-.75-.75V3.622M13.5 21v-7.5a.75.75 0 00-.75-.75h-3a2.25 2.25 0 01-2.25-2.25v-.75a2.25 2.25 0 012.25-2.25h3a.75.75 0 00.75-.75V3.622M13.5 21h-3" />
                        </svg>
                    </div>
                    <h4 class="font-bold text-gray-900 text-xs mb-1 leading-tight" x-text="menu.name"></h4>
                    <p class="text-xs font-extrabold text-indigo-600" x-text="formatCurrency(getPrice(menu))"></p>
                </div>
            </template>
        </div>

        <!-- Floating Cart Summary -->
        <div class="fixed bottom-[72px] left-0 right-0 max-w-md mx-auto px-4 z-40 transition-transform duration-300" :class="cart.length > 0 ? 'translate-y-0' : 'translate-y-32'">
            <div class="bg-gray-900 rounded-2xl shadow-xl p-3 flex justify-between items-center text-white">
                <div class="flex items-center space-x-3 cursor-pointer" @click="showCartModal = true">
                    <div class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8 text-indigo-300">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                        <span x-text="totalItems()" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow-sm"></span>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-semibold uppercase">Total Tagihan</p>
                        <p class="font-bold text-lg leading-none" x-text="formatCurrency(totalPrice())"></p>
                    </div>
                </div>
                
                <button @click="submitSale()" class="bg-emerald-500 hover:bg-emerald-400 px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/30 active:scale-95 transition-transform text-white">
                    Bayar & Simpan
                </button>
            </div>
        </div>

        <!-- Cart Modal -->
        <div x-show="showCartModal" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-md rounded-t-3xl p-5 shadow-2xl max-h-[80vh] overflow-y-auto" @click.away="showCartModal = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Detail Pesanan</h3>
                    <button @click="showCartModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4 mb-6">
                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900" x-text="item.name"></p>
                                <p class="text-xs text-gray-500 mt-0.5" x-text="formatCurrency(item.price)"></p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button @click="decreaseQty(index)" class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center font-bold hover:bg-gray-200">-</button>
                                <span class="font-bold text-gray-900 w-4 text-center" x-text="item.qty"></span>
                                <button @click="increaseQty(index)" class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold hover:bg-indigo-200">+</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Hidden Form to Submit -->
        <form id="pos-form" action="{{ route('pos.store') }}" method="POST" style="display: none;">
            @csrf
            <input type="hidden" name="buyer_type" x-model="buyerType">
            <input type="hidden" name="payment_method" x-model="paymentMethod">
            <input type="hidden" name="cart" :value="JSON.stringify(cart)">
        </form>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posCart', (menusData) => ({
                menus: menusData,
                cart: [],
                buyerType: 'Eceran',
                paymentMethod: 'Tunai',
                showCartModal: false,

                getPrice(menu) {
                    if (this.buyerType === 'Reseller') return parseFloat(menu.price_reseller);
                    if (this.buyerType === 'Agen') return parseFloat(menu.price_agen);
                    return parseFloat(menu.price_eceran);
                },

                getCartItemQty(menuId) {
                    const item = this.cart.find(c => c.id === menuId);
                    return item ? item.qty : 0;
                },

                addToCart(menu) {
                    const price = this.getPrice(menu);
                    const index = this.cart.findIndex(c => c.id === menu.id);
                    if (index >= 0) {
                        this.cart[index].qty++;
                        this.cart[index].price = price; // Update price just in case buyerType changed
                    } else {
                        this.cart.push({
                            id: menu.id,
                            name: menu.name,
                            price: price,
                            qty: 1
                        });
                    }
                },

                increaseQty(index) {
                    this.cart[index].qty++;
                },

                decreaseQty(index) {
                    if (this.cart[index].qty > 1) {
                        this.cart[index].qty--;
                    } else {
                        this.cart.splice(index, 1);
                        if (this.cart.length === 0) {
                            this.showCartModal = false;
                        }
                    }
                },

                totalItems() {
                    return this.cart.reduce((sum, item) => sum + item.qty, 0);
                },

                totalPrice() {
                    return this.cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
                },

                formatCurrency(value) {
                    return 'Rp' + new Intl.NumberFormat('id-ID').format(value);
                },

                submitSale() {
                    if (this.cart.length === 0) return;
                    document.getElementById('pos-form').submit();
                },

                // Watch for buyerType change to update cart prices
                init() {
                    this.$watch('buyerType', (newType) => {
                        this.cart.forEach(item => {
                            const menu = this.menus.find(m => m.id === item.id);
                            if (menu) {
                                item.price = this.getPrice(menu);
                            }
                        });
                    });
                }
            }));
        });
    </script>
</x-app-layout>
