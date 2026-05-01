<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $period = $request->query('period', 'today');
    
    $querySales = \App\Models\DailySale::query();
    $queryPurchases = \App\Models\Purchase::query();
    $queryExpenses = \App\Models\Expense::query();

    if ($period == 'yesterday') {
        $date = \Carbon\Carbon::yesterday();
        $querySales->whereDate('sale_date', $date);
        $queryPurchases->whereDate('purchase_date', $date);
        $queryExpenses->whereDate('expense_date', $date);
    } elseif ($period == 'this_week') {
        $start = \Carbon\Carbon::now()->startOfWeek();
        $end = \Carbon\Carbon::now()->endOfWeek();
        $querySales->whereBetween('sale_date', [$start, $end]);
        $queryPurchases->whereBetween('purchase_date', [$start, $end]);
        $queryExpenses->whereBetween('expense_date', [$start, $end]);
    } elseif ($period == 'this_month') {
        $start = \Carbon\Carbon::now()->startOfMonth();
        $end = \Carbon\Carbon::now()->endOfMonth();
        $querySales->whereBetween('sale_date', [$start, $end]);
        $queryPurchases->whereBetween('purchase_date', [$start, $end]);
        $queryExpenses->whereBetween('expense_date', [$start, $end]);
    } else {
        // default today
        $date = \Carbon\Carbon::today();
        $querySales->whereDate('sale_date', $date);
        $queryPurchases->whereDate('purchase_date', $date);
        $queryExpenses->whereDate('expense_date', $date);
    }

    $sales = $querySales->get();
    $totalRev = $sales->sum('total_revenue');
    $totalHpp = $sales->sum('total_hpp');
    $totalProfit = $sales->sum('gross_profit');
    
    // Total Pengeluaran = Beli Bahan (Purchase) + Biaya Operasional (Expense)
    $totalPengeluaran = $queryPurchases->sum('total_amount') + $queryExpenses->sum('amount');
    
    $kpi = [
        'revenue' => $totalRev,
        'profit'  => $totalProfit,
        'pengeluaran' => $totalPengeluaran,
        'fc_percent' => $totalRev > 0 ? round(($totalHpp / $totalRev) * 100, 2) : 0,
        'current_period' => $period
    ];

    $menus = \App\Models\MenuItem::take(5)->get();

    return view('dashboard', compact('kpi', 'menus'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/spk', [\App\Http\Controllers\SpkController::class, 'index'])->name('spk.index');
    Route::get('/spk/export-pdf', [\App\Http\Controllers\SpkController::class, 'exportPdf'])->name('spk.export');
    
    // Reports & Expenses
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/finance', [\App\Http\Controllers\ReportController::class, 'exportFinance'])->name('reports.finance');
    Route::get('/reports/inventory', [\App\Http\Controllers\ReportController::class, 'exportInventory'])->name('reports.inventory');
    Route::post('/reports/expenses', [\App\Http\Controllers\ReportController::class, 'storeExpense'])->name('reports.store_expense');
    Route::get('/pos', [\App\Http\Controllers\PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [\App\Http\Controllers\PosController::class, 'store'])->name('pos.store');
    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/materials', [\App\Http\Controllers\InventoryController::class, 'materials'])->name('inventory.materials');
    Route::get('/inventory/production', [\App\Http\Controllers\InventoryController::class, 'production'])->name('inventory.production');
    
    // Rute aksi
    Route::post('/inventory/purchase', [\App\Http\Controllers\InventoryController::class, 'storePurchase'])->name('inventory.purchase');
    Route::post('/inventory/opname', [\App\Http\Controllers\InventoryController::class, 'storeOpname'])->name('inventory.opname');
    Route::post('/inventory/produce', [\App\Http\Controllers\InventoryController::class, 'storeProduction'])->name('inventory.produce');

    Route::get('/recipe', [\App\Http\Controllers\RecipeController::class, 'index'])->name('recipe.index');
    Route::post('/recipe', [\App\Http\Controllers\RecipeController::class, 'store'])->name('recipe.store');
});

require __DIR__.'/auth.php';
