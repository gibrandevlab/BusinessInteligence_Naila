<x-app-layout>
    <div class="px-3 pt-4 pb-6">
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Manajemen Stok</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">Pilih kategori inventori yang ingin Anda kelola</p>

        <div class="space-y-4">
            <!-- Card 1: Bahan Baku -->
            <a href="{{ route('inventory.materials') }}" class="block bg-gradient-to-br from-indigo-500 to-blue-600 rounded-3xl p-6 shadow-lg shadow-blue-200 text-white relative overflow-hidden active:scale-95 transition-transform">
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-1">Gudang Bahan Baku</h3>
                <p class="text-indigo-100 text-xs w-3/4 mb-4 leading-relaxed">Cek sisa bahan mentah (beras, daging, sayur), tambah stok baru, atau laporkan bahan rusak/susut.</p>
                <div class="inline-flex items-center text-sm font-bold text-white bg-white/20 px-3 py-1.5 rounded-full">
                    Masuk Gudang &rarr;
                </div>
            </a>

            <!-- Card 2: Stok Siap Jual -->
            <a href="{{ route('inventory.production') }}" class="block bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 shadow-lg shadow-emerald-200 text-white relative overflow-hidden active:scale-95 transition-transform">
                <div class="absolute -right-4 -bottom-4 opacity-20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-1">Stok Siap Jual (Menu)</h3>
                <p class="text-emerald-100 text-xs w-3/4 mb-4 leading-relaxed">Lihat batas maksimal porsi yang bisa Anda jual hari ini berdasarkan sisa bahan di gudang.</p>
                <div class="inline-flex items-center text-sm font-bold text-white bg-white/20 px-3 py-1.5 rounded-full">
                    Lihat Etalase &rarr;
                </div>
            </a>
        </div>
    </div>
</x-app-layout>
