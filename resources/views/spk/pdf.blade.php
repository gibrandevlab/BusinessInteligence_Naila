<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Analisis Menu (SPK)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #111827;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #6B7280;
            font-size: 14px;
        }
        .benchmark-box {
            background-color: #F3F4F6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .benchmark-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #374151;
            text-align: center;
        }
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .stats-grid td {
            width: 50%;
            text-align: center;
        }
        .stats-value {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #E5E7EB;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }
        table.data-table th {
            background-color: #F9FAFB;
            font-weight: bold;
            color: #374151;
        }
        .cat-Star { color: #059669; font-weight: bold; }
        .cat-Plowhorse { color: #2563EB; font-weight: bold; }
        .cat-Puzzle { color: #D97706; font-weight: bold; }
        .cat-Dog { color: #DC2626; font-weight: bold; }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN ANALISIS KEPUTUSAN MENU</h1>
        <p>Aplikasi Produksi 876 ASIAW - Periode: {{ $days }} Hari Terakhir</p>
        <p style="font-size: 11px;">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="benchmark-box">
        <h3>Benchmark Sistem (Nilai Rata-Rata)</h3>
        <table class="stats-grid">
            <tr>
                <td>
                    <div style="font-size: 12px; color: #6B7280; text-transform: uppercase;">Standar Laris Terjual</div>
                    <div class="stats-value">{{ number_format($avg_mm, 2) }}%</div>
                </td>
                <td>
                    <div style="font-size: 12px; color: #6B7280; text-transform: uppercase;">Standar Keuntungan / Porsi</div>
                    <div class="stats-value">Rp {{ number_format($avg_cm, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <h3 style="font-size: 16px; margin-bottom: 5px;">Rincian Rekomendasi Menu</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="20%">Nama Menu</th>
                <th width="12%">Kategori</th>
                <th width="13%">Laris (Penjualan)</th>
                <th width="15%">Cuan (Untung/Porsi)</th>
                <th width="40%">Saran Aksi Eksekutif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($menus as $menu)
                <tr>
                    <td style="font-weight: bold;">{{ $menu->name }}</td>
                    <td class="cat-{{ $menu->category }}">
                        @if($menu->category == 'Star')
                            ⭐ Sangat laku dan keuntungan besar
                        @elseif($menu->category == 'Plowhorse')
                            🐴 Sangat laku, tapi keuntungan sedikit
                        @elseif($menu->category == 'Puzzle')
                            🧩 Kurang laku, tapi keuntungan besar
                        @else
                            🐶 Kurang laku dan keuntungan sedikit
                        @endif
                    </td>
                    <td>
                        {{ number_format($menu->mm_percent, 1) }}%<br>
                        <span style="font-size: 10px; color: {{ $menu->mm_percent >= $avg_mm ? '#059669' : '#DC2626' }}">
                            ({{ $menu->mm_percent >= $avg_mm ? 'Di atas standar' : 'Di bawah standar' }})
                        </span>
                    </td>
                    <td>
                        Rp{{ number_format($menu->cm_per_item, 0, ',', '.') }}<br>
                        <span style="font-size: 10px; color: {{ $menu->cm_per_item >= $avg_cm ? '#059669' : '#DC2626' }}">
                            ({{ $menu->cm_per_item >= $avg_cm ? 'Di atas standar' : 'Di bawah standar' }})
                        </span>
                    </td>
                    <td style="color: #4B5563;">{{ $menu->action }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem SPK Aplikasi Produksi 876 ASIAW. Data yang ditampilkan adalah data riil dari aktivitas Kasir.
    </div>

</body>
</html>
