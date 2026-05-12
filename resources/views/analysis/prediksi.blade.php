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
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">AI Prediksi Pesanan</h2>
                <p class="text-sm text-gray-500 mt-1">Tebak jumlah porsi untuk besok</p>
            </div>
        </div>

        {{-- Penjelasan Singkat --}}
        <div class="bg-sky-50 rounded-2xl p-4 mb-6 border border-sky-100">
            <p class="text-[11px] text-sky-800 leading-relaxed text-justify">
                <strong>Apa kegunaan fitur ini?</strong> AI akan membaca tren naik-turunnya pesanan di hari-hari sebelumnya, lalu mencoba <strong>menebak berapa porsi yang mungkin laku esok hari</strong>. Fitur ini sangat berguna agar Anda bisa menyetok bahan masakan dengan pas (tidak kurang, tidak membusuk).
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

        {{-- HASIL PREDIKSI --}}
        <h3 class="font-bold text-gray-900 mb-3 ml-1">Tebakan Penjualan Mendatang:</h3>
        
        @if(empty($regressionResult['predictions']))
            <div class="text-center py-10 text-gray-400 bg-white rounded-3xl border border-gray-100 shadow-sm">
                <p class="text-sm font-semibold">Belum ada data penjualan</p>
                <p class="text-xs mt-1">Sistem butuh data minimal beberapa hari untuk mulai menebak.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($regressionResult['predictions'] as $pred)
                <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-gray-900 text-base">{{ $pred['name'] }}</h4>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold
                            {{ $pred['trend'] === 'naik' ? 'bg-emerald-100 text-emerald-700' : ($pred['trend'] === 'turun' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ $pred['trend'] === 'naik' ? '📈 Tren Naik' : ($pred['trend'] === 'turun' ? '📉 Tren Turun' : '➡️ Penjualan Stabil') }}
                        </span>
                    </div>

                    <p class="text-xs text-gray-600 mb-2">Berdasarkan data {{ $pred['data_points'] }} hari terakhir (Rata-rata: {{ $pred['avg_daily'] }} porsi/hari), AI menebak penjualan untuk hari berikutnya adalah:</p>

                    <div class="grid grid-cols-3 gap-2">
                        @foreach($pred['predictions'] as $p)
                        <div class="bg-sky-50 rounded-xl p-3 text-center border border-sky-100">
                            <p class="text-[10px] text-sky-700 font-bold mb-1">{{ $p['day_label'] }}</p>
                            <div class="text-lg font-black text-sky-900">{{ $p['predicted_qty'] }}</div>
                            <p class="text-[9px] text-gray-500 mt-1">porsi</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 text-right">
                        <span class="text-[10px] text-gray-400 font-medium">Akurasi AI: {{ $pred['confidence'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
