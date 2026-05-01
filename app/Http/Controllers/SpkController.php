<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItem;
use App\Models\DailySaleItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class SpkController extends Controller
{
    public function index(Request $request)
    {
        // Filter periode (Default: 30 hari terakhir)
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // 1. Ambil data penjualan berdasarkan periode
        $salesData = DailySaleItem::select(
                'menu_item_id',
                DB::raw('SUM(qty_sold) as total_qty'),
                DB::raw('SUM(contribution_margin) as total_margin')
            )
            ->whereHas('dailySale', function($query) use ($startDate, $endDate) {
                $query->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->groupBy('menu_item_id')
            ->get();

        if ($salesData->isEmpty()) {
            return view('spk.index', [
                'days' => $days,
                'menus' => collect(),
                'avg_cm' => 0,
                'avg_mm' => 0,
                'total_qty_all' => 0
            ]);
        }

        // 2. Kalkulasi Rata-Rata Sistem (Benchmark)
        $totalQtyAll = $salesData->sum('total_qty');
        $totalMarginAll = $salesData->sum('total_margin');
        $numberOfMenus = $salesData->count();

        // Rata-rata CM per item
        $averageCM = $totalQtyAll > 0 ? ($totalMarginAll / $totalQtyAll) : 0;
        
        // Threshold Popularitas (Menu Mix) => (1 / N) * 0.7 
        $averageMM = $numberOfMenus > 0 ? (1 / $numberOfMenus) * 0.7 * 100 : 0;

        // 3. Klasifikasi Setiap Menu
        $menus = [];
        $menuItems = MenuItem::whereIn('id', $salesData->pluck('menu_item_id'))->get()->keyBy('id');

        foreach ($salesData as $data) {
            $menu = $menuItems->get($data->menu_item_id);
            if (!$menu) continue;

            $qty = (int) $data->total_qty;
            $cmPerItem = $qty > 0 ? ($data->total_margin / $qty) : 0;
            $mmPercent = $totalQtyAll > 0 ? ($qty / $totalQtyAll) * 100 : 0;

            // Kategori BCG
            $category = '';
            $color = '';
            $action = '';
            
            if ($mmPercent >= $averageMM && $cmPerItem >= $averageCM) {
                $category = 'Star';
                $color = 'bg-emerald-500';
                $action = 'Pertahankan kualitas & jadikan signature dish.';
            } elseif ($mmPercent >= $averageMM && $cmPerItem < $averageCM) {
                $category = 'Plowhorse';
                $color = 'bg-amber-500';
                $action = 'Kurangi porsi sedikit atau naikkan harga secara bertahap.';
            } elseif ($mmPercent < $averageMM && $cmPerItem >= $averageCM) {
                $category = 'Puzzle';
                $color = 'bg-blue-500';
                $action = 'Promosikan lebih gencar (bundling/diskon).';
            } else {
                $category = 'Dog';
                $color = 'bg-red-500';
                $action = 'Evaluasi resep atau hapus dari menu jika tidak prospek.';
            }

            $menus[] = (object) [
                'name' => $menu->name,
                'qty' => $qty,
                'cm_per_item' => $cmPerItem,
                'mm_percent' => $mmPercent,
                'category' => $category,
                'color' => $color,
                'action' => $action
            ];
        }

        // Sort: Stars -> Plowhorse -> Puzzle -> Dog
        $order = ['Star' => 1, 'Plowhorse' => 2, 'Puzzle' => 3, 'Dog' => 4];
        usort($menus, function($a, $b) use ($order) {
            return $order[$a->category] <=> $order[$b->category];
        });

        return view('spk.index', [
            'days' => $days,
            'menus' => collect($menus),
            'avg_cm' => $averageCM,
            'avg_mm' => $averageMM,
            'total_qty_all' => $totalQtyAll
        ]);
    }

    public function exportPdf(Request $request)
    {
        $days = $request->query('days', 30);
        $startDate = \Carbon\Carbon::today()->subDays($days);
        
        $sales = \App\Models\DailySaleItem::whereHas('dailySale', function($q) use ($startDate) {
            $q->whereDate('sale_date', '>=', $startDate);
        })->get();

        $menus = \App\Models\MenuItem::all();
        $total_sold_all = 0;
        
        // Perhitungan Dasar
        foreach ($menus as $menu) {
            $menu_sales = $sales->where('menu_item_id', $menu->id);
            $menu->total_sold = $menu_sales->sum('qty_sold');
            $menu->total_profit = $menu_sales->sum('contribution_margin');
            
            $total_sold_all += $menu->total_sold;
            
            // Hitung rata-rata margin (CM) per porsi
            if ($menu->total_sold > 0) {
                $menu->cm_per_item = $menu->total_profit / $menu->total_sold;
            } else {
                $menu->cm_per_item = 0;
            }
        }

        // Jika tidak ada penjualan
        if ($total_sold_all == 0) {
            return redirect()->back()->with('error', 'Tidak ada data penjualan untuk di-export.');
        }

        // Menu Mix (MM) %
        $total_cm = 0;
        $active_menus_count = 0;
        
        foreach ($menus as $menu) {
            if ($menu->total_sold > 0) {
                $menu->mm_percent = ($menu->total_sold / $total_sold_all) * 100;
                $total_cm += $menu->cm_per_item;
                $active_menus_count++;
            } else {
                $menu->mm_percent = 0;
            }
        }

        // Benchmark
        $avg_mm = (1 / $menus->count()) * 0.7 * 100;
        $avg_cm = $active_menus_count > 0 ? ($total_cm / $active_menus_count) : 0;

        // Kategorisasi
        foreach ($menus as $menu) {
            if ($menu->total_sold == 0) {
                $menu->category = 'Dog';
                $menu->action = 'Hapus dari menu atau pertahankan hanya jika bahan bakunya sangat awet dan disukai segmen tertentu (niche).';
                $menu->color = 'bg-red-500';
                continue;
            }

            $is_high_mm = $menu->mm_percent >= $avg_mm;
            $is_high_cm = $menu->cm_per_item >= $avg_cm;

            if ($is_high_mm && $is_high_cm) {
                $menu->category = 'Star';
                $menu->action = 'Jaga kualitas sekuat tenaga! Pertahankan porsi dan rasa.';
                $menu->color = 'bg-emerald-500';
            } elseif ($is_high_mm && !$is_high_cm) {
                $menu->category = 'Plowhorse';
                $menu->action = 'Sangat populer, tapi untung tipis. Coba naikkan harga sedikit.';
                $menu->color = 'bg-amber-500';
            } elseif (!$is_high_mm && $is_high_cm) {
                $menu->category = 'Puzzle';
                $menu->action = 'Untungnya besar tapi kurang laku. Lakukan promosi gencar.';
                $menu->color = 'bg-blue-500';
            } else {
                $menu->category = 'Dog';
                $menu->action = 'Hapus dari daftar menu. Bahan baku terbuang sia-sia.';
                $menu->color = 'bg-red-500';
            }
        }

        // Sort by category importance (Star -> Plowhorse -> Puzzle -> Dog)
        $menus = $menus->sortBy(function($menu) {
            $order = ['Star' => 1, 'Plowhorse' => 2, 'Puzzle' => 3, 'Dog' => 4];
            return $order[$menu->category];
        });

        // Generate PDF
        $pdf = Pdf::loadView('spk.pdf', compact('menus', 'days', 'avg_mm', 'avg_cm'));
        
        return $pdf->download('Laporan_Analisis_Menu_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }
}
