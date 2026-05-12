<x-app-layout>
    <div class="px-3 pt-4 pb-24">
        {{-- Header --}}
        <div class="flex items-center space-x-3 mb-6">
            <a href="{{ route('reports.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">AI Peringatan Dini</h2>
                <p class="text-sm text-gray-500 mt-1">Prediksi Pergeseran Kategori Menu</p>
            </div>
        </div>

        {{-- Penjelasan Singkat --}}
        <div class="bg-purple-50 rounded-2xl p-4 mb-6 border border-purple-100">
            <p class="text-[11px] text-purple-800 leading-relaxed text-justify">
                <strong>Bedanya dengan Halaman SPK?</strong> Halaman SPK menampilkan performa menu <strong>Masa Kini</strong>. Sedangkan fitur AI ini berfungsi sebagai <em>Early Warning System</em> untuk menebak nasib menu di <strong>Masa Depan</strong>. AI membandingkan status menu saat ini dengan tren grafiknya untuk mendeteksi dini apakah menu Bintang Anda berisiko jatuh, atau menu buruk (Dog) Anda masih bisa diselamatkan.
            </p>
        </div>

        {{-- WARNING DATA --}}
        @php $c = $dataScore['color']; @endphp
        <div class="bg-{{ $c }}-50 border border-{{ $c }}-200 rounded-2xl p-4 mb-6">
            <div class="flex items-start space-x-3">
                <div class="bg-{{ $c }}-100 p-2 rounded-xl flex-shrink-0">
                    <span class="text-xl">
                        @if($dataScore['level'] === 'low') 🚨
                        @elseif($dataScore['level'] === 'moderate') ⚠️
                        @else ✅ @endif
                    </span>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-{{ $c }}-900 text-sm">Status Kecerdasan AI: {{ $dataScore['label'] }}</h4>
                    <p class="text-xs text-{{ $c }}-800 mt-1 leading-relaxed text-justify">{{ $dataScore['message'] }}</p>
                </div>
            </div>
        </div>

        {{-- HASIL KLASIFIKASI --}}
        <h3 class="font-bold text-gray-900 mb-3 ml-1">Prediksi AI Bulan Depan:</h3>
        
        @if(empty($classificationResult['menus']))
            <div class="text-center py-10 text-gray-400 bg-white rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-sm font-semibold">Belum ada data transaksi</p>
                <p class="text-xs mt-1">Sistem butuh data tren beberapa hari</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($classificationResult['menus'] as $menu)
                @php
                    $colors = [
                        'danger' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-700', 'icon' => '🚨'],
                        'warning' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'badge' => 'bg-amber-100 text-amber-700', 'icon' => '⚠️'],
                        'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'badge' => 'bg-emerald-100 text-emerald-700', 'icon' => '🚀'],
                        'info' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'badge' => 'bg-blue-100 text-blue-700', 'icon' => 'ℹ️'],
                    ];
                    $c = $colors[$menu['alert_type']];
                @endphp
                <div class="bg-white border {{ $c['border'] }} shadow-sm rounded-3xl p-5">
                    
                    {{-- Header Card --}}
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900 text-lg">{{ $menu['name'] }}</h4>
                            <div class="text-[10px] text-gray-500 mt-1">
                                Saat ini: <strong class="text-gray-800">{{ $menu['current_category'] }}</strong> | 
                                Tren: 
                                <strong class="{{ $menu['trend'] == 'naik' ? 'text-emerald-600' : ($menu['trend'] == 'turun' ? 'text-red-600' : 'text-gray-600') }}">
                                    {{ strtoupper($menu['trend']) }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    {{-- Prediksi AI --}}
                    <div class="{{ $c['bg'] }} rounded-xl p-3 border {{ $c['border'] }} mb-3">
                        <div class="flex items-center space-x-2 mb-1">
                            <span>{{ $c['icon'] }}</span>
                            <p class="text-[10px] font-bold uppercase {{ str_replace('bg-', 'text-', $c['badge']) }}">Prediksi AI: {{ $menu['future_category'] }}</p>
                        </div>
                        <p class="text-sm font-bold text-gray-900 mt-1 leading-snug">{{ $menu['future_prediction'] }}</p>
                    </div>

                    <div class="mt-4 flex justify-between items-center border-t border-gray-100 pt-3">
                        <div class="text-xs text-gray-500">
                            Total Historis: <strong class="text-gray-900">{{ number_format($menu['total_qty']) }} porsi</strong>
                        </div>
                        <div class="text-[10px] bg-gray-100 text-gray-700 px-2 py-1 rounded-lg font-bold">
                            Akurasi AI: {{ $menu['confidence'] }}%
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
