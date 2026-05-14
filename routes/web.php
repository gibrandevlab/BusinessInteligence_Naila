<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\BiHubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SpkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Sistem Penunjang Keputusan (Decision Support System) ─────────────────
    Route::get('/spk', [SpkController::class, 'index'])->name('spk.index');
    Route::get('/spk/export-pdf', [SpkController::class, 'exportPdf'])->name('spk.export');

    // ── Reports & Expenses ────────────────────────────────────────────────
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/finance', [ReportController::class, 'exportFinance'])->name('reports.finance');
    Route::get('/reports/inventory', [ReportController::class, 'exportInventory'])->name('reports.inventory');
    Route::post('/reports/expenses', [ReportController::class, 'storeExpense'])->name('reports.store_expense');

    // ── POS (Point of Sale) ───────────────────────────────────────────────
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');

    // ── Inventory ─────────────────────────────────────────────────────────
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/materials', [InventoryController::class, 'materials'])->name('inventory.materials');
    Route::get('/inventory/production', [InventoryController::class, 'production'])->name('inventory.production');
    Route::post('/inventory/purchase', [InventoryController::class, 'storePurchase'])->name('inventory.purchase');
    Route::post('/inventory/opname', [InventoryController::class, 'storeOpname'])->name('inventory.opname');
    Route::post('/inventory/produce', [InventoryController::class, 'storeProduction'])->name('inventory.produce');
    Route::post('/inventory/production-opname', [InventoryController::class, 'storeProductionOpname'])->name('inventory.production.opname');

    // ── Analisis Data ─────────────────────────────────────────────────────
    Route::get('/analysis/klasifikasi', [AnalysisController::class, 'klasifikasi'])->name('analysis.klasifikasi');
    Route::get('/analysis/prediksi', [AnalysisController::class, 'prediksi'])->name('analysis.prediksi');

    // ── BI Hub (Business Intelligence) ───────────────────────────────────
    Route::get('/reports/sales-trend', [BiHubController::class, 'salesTrend'])->name('reports.sales_trend');
    Route::get('/reports/menu-ranking', [BiHubController::class, 'menuRanking'])->name('reports.menu_ranking');
    Route::get('/reports/waste-analysis', [BiHubController::class, 'wasteAnalysis'])->name('reports.waste_analysis');

    // ── Recipe Management ─────────────────────────────────────────────────
    Route::get('/recipe', [RecipeController::class, 'index'])->name('recipe.index');
    Route::post('/recipe', [RecipeController::class, 'store'])->name('recipe.store');
    Route::get('/recipe/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipe.edit');
    Route::put('/recipe/{recipe}', [RecipeController::class, 'update'])->name('recipe.update');
    Route::delete('/recipe/{recipe}', [RecipeController::class, 'destroy'])->name('recipe.destroy');
});

require __DIR__.'/auth.php';
