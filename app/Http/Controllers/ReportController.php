<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\DailySale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function exportFinance(Request $request)
    {
        $period = $request->query('period', 'this_month');
        
        $querySales = DailySale::query();
        $queryPurchases = Purchase::query();
        $queryExpenses = Expense::query();

        if ($period == 'today') {
            $date = Carbon::today();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
            $periodLabel = "Hari Ini (" . $date->translatedFormat('d F Y') . ")";
        } elseif ($period == 'this_week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = "Minggu Ini (" . $start->translatedFormat('d M Y') . " - " . $end->translatedFormat('d M Y') . ")";
        } elseif ($period == 'this_month') {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = "Bulan Ini (" . Carbon::now()->translatedFormat('F Y') . ")";
        } else {
            $periodLabel = "Semua Waktu";
        }

        $sales = $querySales->get();
        $purchases = $queryPurchases->get();
        $expenses = $queryExpenses->get();

        $totalRevenue = $sales->sum('total_revenue');
        $totalHpp = $sales->sum('total_hpp');
        $grossProfit = $sales->sum('gross_profit');
        
        $totalPurchases = $purchases->sum('total_amount');
        $totalExpenses = $expenses->sum('amount');
        
        // Asumsi: Laba Bersih = Laba Kotor - Pengeluaran Riil (Expense Operasional)
        $netProfit = $grossProfit - $totalExpenses;

        $pdf = Pdf::loadView('reports.finance_pdf', compact(
            'periodLabel', 'sales', 'purchases', 'expenses',
            'totalRevenue', 'totalHpp', 'grossProfit', 'totalPurchases', 'totalExpenses', 'netProfit'
        ));
        
        return $pdf->download('Laporan_Keuangan_876_ASIAW_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    public function exportInventory()
    {
        $ingredients = Ingredient::orderBy('name')->get();
        $menus = MenuItem::orderBy('name')->get();

        $totalAssetValue = 0;
        foreach ($ingredients as $item) {
            $totalAssetValue += ($item->current_stock * $item->cost_per_unit);
        }

        $pdf = Pdf::loadView('reports.inventory_pdf', compact('ingredients', 'menus', 'totalAssetValue'));
        
        return $pdf->download('Laporan_Aset_Inventori_876_ASIAW_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date'
        ]);

        Expense::create([
            'user_id' => auth()->id(),
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
        ]);

        return redirect()->back()->with('success', 'Pengeluaran operasional berhasil dicatat!');
    }
}
