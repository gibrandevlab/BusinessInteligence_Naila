<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailySaleItem;
use App\Models\DailySale;
use App\Models\MenuItem;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    /**
     * Halaman AI Klasifikasi Menu (Decision Tree)
     */
    public function klasifikasi()
    {
        $dataScore = $this->calculateDataScore();
        $classificationResult = $this->runClassification();

        return view('analysis.klasifikasi', compact('dataScore', 'classificationResult'));
    }

    /**
     * Halaman AI Prediksi Penjualan (Linear Regression)
     */
    public function prediksi()
    {
        $dataScore = $this->calculateDataScore();
        $regressionResult = $this->runRegression();

        return view('analysis.prediksi', compact('dataScore', 'regressionResult'));
    }

    /**
     * Hitung skor kepercayaan data (0-100%)
     */
    private function calculateDataScore(): array
    {
        $days = DailySale::select('sale_date')->distinct()->count();
        $records = DailySaleItem::count();
        $menus = MenuItem::count();

        // Minimal ideal: 30 hari, 100 record, 3 menu
        $dayScore = min(($days / 30) * 100, 100);
        $recordScore = min(($records / 100) * 100, 100);
        $menuScore = min(($menus / 3) * 100, 100);

        $overall = round(($dayScore + $recordScore + $menuScore) / 3);

        if ($overall >= 70) {
            $level = 'good';
            $label = 'Cukup Baik';
            $color = 'emerald';
            $message = 'Sistem sudah memiliki cukup data untuk mulai mengenali pola penjualan Anda.';
        } elseif ($overall >= 40) {
            $level = 'moderate';
            $label = 'Sedang';
            $color = 'amber';
            $message = 'Tebakan AI masih bisa meleset. Terus gunakan aplikasi untuk memperbanyak data.';
        } else {
            $level = 'low';
            $label = 'Masih Sedikit';
            $color = 'red';
            $message = 'Karena aplikasi baru saja digunakan, fitur AI saat ini masih "belajar" dan akurasinya belum bisa dijadikan patokan utama. Tebakan akan semakin akurat setelah data transaksi harian terkumpul lebih banyak.';
        }

        return [
            'overall' => $overall,
            'level' => $level,
            'label' => $label,
            'color' => $color,
            'message' => $message,
            'detail' => [
                'days' => ['value' => $days, 'target' => 30, 'percent' => round($dayScore)],
                'records' => ['value' => $records, 'target' => 100, 'percent' => round($recordScore)],
                'menus' => ['value' => $menus, 'target' => 3, 'percent' => round($menuScore)],
            ]
        ];
    }

    /**
     * AI Klasifikasi: Early Warning System (Sistem Peringatan Dini)
     * Memprediksi apakah sebuah menu akan naik/turun kelas di masa depan berdasarkan tren.
     */
    private function runClassification(): array
    {
        // 1. Dapatkan semua menu item yang pernah terjual
        $soldMenuIds = DailySaleItem::select('menu_item_id')->distinct()->pluck('menu_item_id');
        $menuItems = MenuItem::whereIn('id', $soldMenuIds)->get()->keyBy('id');

        if ($soldMenuIds->isEmpty()) {
            return ['menus' => []];
        }

        $menus = [];
        $totalQtyAll = DailySaleItem::sum('qty_sold');
        $totalMarginAll = DailySaleItem::sum('contribution_margin');
        $numberOfMenus = $soldMenuIds->count();

        // Benchmark masa kini (Keseluruhan)
        $averageCM = $totalQtyAll > 0 ? ($totalMarginAll / $totalQtyAll) : 0;
        $averageMM = $numberOfMenus > 0 ? (1 / $numberOfMenus) * 0.7 * 100 : 0;

        foreach ($soldMenuIds as $menuId) {
            $menu = $menuItems->get($menuId);
            if (!$menu)
                continue;

            // Ambil penjualan historis (diurutkan berdasarkan tanggal)
            $sales = DailySaleItem::where('menu_item_id', $menuId)
                ->join('daily_sales', 'daily_sale_items.daily_sale_id', '=', 'daily_sales.id')
                ->orderBy('daily_sales.sale_date', 'asc')
                ->get();

            $totalQty = $sales->sum('qty_sold');
            if ($totalQty == 0)
                continue;

            $cmPerItem = $sales->sum('contribution_margin') / $totalQty;
            $mmPercent = ($totalQty / max($totalQtyAll, 1)) * 100;

            // Tentukan status Masa Kini (BCG Origin)
            $currentCategory = '';
            if ($mmPercent >= $averageMM && $cmPerItem >= $averageCM)
                $currentCategory = 'Star';
            elseif ($mmPercent >= $averageMM && $cmPerItem < $averageCM)
                $currentCategory = 'Plowhorse';
            elseif ($mmPercent < $averageMM && $cmPerItem >= $averageCM)
                $currentCategory = 'Puzzle';
            else
                $currentCategory = 'Dog';

            // Hitung Tren Penjualan (Membagi data jadi 2 periode: Lama vs Baru)
            // Sederhananya, jika penjualan di paruh kedua lebih besar dari paruh pertama = Tren Naik
            $halfCount = floor($sales->count() / 2);
            $trend = 'stabil';
            if ($halfCount > 0) {
                $firstHalfQty = $sales->take($halfCount)->sum('qty_sold');
                $secondHalfQty = $sales->skip($halfCount)->sum('qty_sold');

                if ($secondHalfQty > $firstHalfQty * 1.1) { // naik > 10%
                    $trend = 'naik';
                } elseif ($secondHalfQty < $firstHalfQty * 0.9) { // turun > 10%
                    $trend = 'turun';
                }
            }

            // DECISION TREE: Prediksi Kategori di Masa Depan berdasarkan Current Category & Trend
            $futurePrediction = '';
            $futureCategory = '';
            $alertType = 'info'; // success, warning, danger
            $confidence = 0;

            if ($currentCategory === 'Star') {
                if ($trend === 'turun') {
                    $futurePrediction = 'Berisiko Turun Menjadi Dog/Plowhorse. Perlu inovasi segar atau promo!';
                    $futureCategory = 'Berisiko Turun';
                    $alertType = 'warning';
                    $confidence = 85;
                } else {
                    $futurePrediction = 'Aman di Posisi Puncak. Penjualan konsisten stabil/naik.';
                    $futureCategory = 'Tetap Bintang';
                    $alertType = 'success';
                    $confidence = 95;
                }
            } elseif ($currentCategory === 'Plowhorse') {
                if ($trend === 'naik' && $cmPerItem > ($averageCM * 0.8)) {
                    $futurePrediction = 'Berpotensi Naik Menjadi Bintang (Star). Coba naikkan harga jual sedikit untuk tes pasar.';
                    $futureCategory = 'Potensi Naik (Star)';
                    $alertType = 'success';
                    $confidence = 80;
                } elseif ($trend === 'turun') {
                    $futurePrediction = 'Berisiko Turun Menjadi Dog. Popularitas mulai menurun tajam.';
                    $futureCategory = 'Berisiko Turun';
                    $alertType = 'danger';
                    $confidence = 85;
                } else {
                    $futurePrediction = 'Aman sebagai andalan volume (Plowhorse).';
                    $futureCategory = 'Stabil (Plowhorse)';
                    $alertType = 'info';
                    $confidence = 90;
                }
            } elseif ($currentCategory === 'Puzzle') {
                if ($trend === 'naik') {
                    $futurePrediction = 'Berpotensi Naik Menjadi Bintang (Star). Permintaan mulai terbentuk, genjot pemasaran!';
                    $futureCategory = 'Potensi Naik (Star)';
                    $alertType = 'success';
                    $confidence = 80;
                } else {
                    $futurePrediction = 'Tertahan di posisi Puzzle. Keuntungan besar tapi sulit terjual.';
                    $futureCategory = 'Stabil (Puzzle)';
                    $alertType = 'warning';
                    $confidence = 75;
                }
            } else { // Dog
                if ($trend === 'turun') {
                    $futurePrediction = 'Kritis (Dead Stock). Pertimbangkan untuk menghapus menu ini sepenuhnya.';
                    $futureCategory = 'Kritis (Dead Stock)';
                    $alertType = 'danger';
                    $confidence = 90;
                } elseif ($trend === 'naik') {
                    $futurePrediction = 'Menunjukkan tanda kebangkitan. Pantau terus penjualannya.';
                    $futureCategory = 'Tanda Pemulihan';
                    $alertType = 'info';
                    $confidence = 70;
                } else {
                    $futurePrediction = 'Tetap berada di kategori bawah (Dog). Beban penyimpanan bahan.';
                    $futureCategory = 'Tetap Dog';
                    $alertType = 'danger';
                    $confidence = 85;
                }
            }

            $menus[] = [
                'name' => $menu->name,
                'current_category' => $currentCategory,
                'trend' => $trend,
                'future_prediction' => $futurePrediction,
                'future_category' => $futureCategory,
                'alert_type' => $alertType,
                'confidence' => $confidence,
                'total_qty' => $totalQty
            ];
        }

        // Urutkan berdasarkan tingkat urgensi (danger -> warning -> success -> info)
        $priority = ['danger' => 1, 'warning' => 2, 'success' => 3, 'info' => 4];
        usort($menus, function ($a, $b) use ($priority) {
            return $priority[$a['alert_type']] <=> $priority[$b['alert_type']];
        });

        return [
            'menus' => $menus,
        ];
    }

    /**
     * Simulasi Linear Regression — Prediksi Penjualan
     */
    private function runRegression(): array
    {
        $salesByDay = DailySaleItem::select(
            'menu_item_id',
            DB::raw('DATE(daily_sales.sale_date) as sale_date'),
            DB::raw('SUM(qty_sold) as daily_qty')
        )
            ->join('daily_sales', 'daily_sale_items.daily_sale_id', '=', 'daily_sales.id')
            ->groupBy('menu_item_id', DB::raw('DATE(daily_sales.sale_date)'))
            ->orderBy(DB::raw('DATE(daily_sales.sale_date)'))
            ->get();

        if ($salesByDay->isEmpty()) {
            return ['predictions' => []];
        }

        $menuItems = MenuItem::all()->keyBy('id');
        $predictions = [];
        $grouped = $salesByDay->groupBy('menu_item_id');

        foreach ($grouped as $menuId => $dailyData) {
            $menu = $menuItems->get($menuId);
            if (!$menu)
                continue;

            $n = $dailyData->count();
            $xValues = range(1, $n);
            $yValues = $dailyData->pluck('daily_qty')->toArray();

            $sumX = array_sum($xValues);
            $sumY = array_sum($yValues);
            $sumXY = 0;
            $sumX2 = 0;

            for ($i = 0; $i < $n; $i++) {
                $sumXY += $xValues[$i] * $yValues[$i];
                $sumX2 += $xValues[$i] * $xValues[$i];
            }

            $meanX = $sumX / $n;
            $meanY = $sumY / $n;

            $denominator = ($n * $sumX2) - ($sumX * $sumX);
            $b = $denominator == 0 ? 0 : (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
            $a = $meanY - ($b * $meanX);

            // Prediksi hari berikutnya (3 hari ke depan)
            $nextDayPredictions = [];
            for ($day = 1; $day <= 3; $day++) {
                $predictedX = $n + $day;
                $predictedY = max(0, round($a + ($b * $predictedX)));
                $nextDayPredictions[] = [
                    'day_label' => 'Besoknya lagi',
                    'predicted_qty' => $predictedY
                ];
            }
            if (isset($nextDayPredictions[0]))
                $nextDayPredictions[0]['day_label'] = 'Besok';
            if (isset($nextDayPredictions[1]))
                $nextDayPredictions[1]['day_label'] = 'Lusa';

            $predictions[] = [
                'name' => $menu->name,
                'data_points' => $n,
                'trend' => $b > 0.5 ? 'naik' : ($b < -0.5 ? 'turun' : 'stabil'),
                'predictions' => $nextDayPredictions,
                'avg_daily' => round($meanY),
                'confidence' => $n > 10 ? 'Cukup' : 'Rendah (Data Kurang)'
            ];
        }

        return [
            'predictions' => $predictions,
        ];
    }
}
