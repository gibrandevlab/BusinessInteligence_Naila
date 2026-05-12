<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\Expense;
use App\Models\MenuItem;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'today');

        $querySales     = DailySale::query();
        $queryPurchases = Purchase::query();
        $queryExpenses  = Expense::query();

        if ($period === 'yesterday') {
            $date = Carbon::yesterday();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
        } elseif ($period === 'this_week') {
            $start = Carbon::now()->startOfWeek();
            $end   = Carbon::now()->endOfWeek();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
        } elseif ($period === 'this_month') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
        } else {
            // default: today
            $date = Carbon::today();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
        }

        $sales     = $querySales->get();
        $totalRev  = $sales->sum('total_revenue');
        $totalHpp  = $sales->sum('total_hpp');
        $totalProfit = $sales->sum('gross_profit');

        // Total Pengeluaran = Beli Bahan (Purchase) + Biaya Operasional (Expense)
        $totalPengeluaran = $queryPurchases->sum('total_amount') + $queryExpenses->sum('amount');

        $kpi = [
            'revenue'        => $totalRev,
            'profit'         => $totalProfit,
            'pengeluaran'    => $totalPengeluaran,
            'fc_percent'     => $totalRev > 0 ? round(($totalHpp / $totalRev) * 100, 2) : 0,
            'current_period' => $period,
        ];

        $menus = MenuItem::take(5)->get();

        return view('dashboard', compact('kpi', 'menus'));
    }
}
