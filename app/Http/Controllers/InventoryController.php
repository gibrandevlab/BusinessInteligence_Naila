<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ingredient;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function materials()
    {
        $ingredients = \App\Models\Ingredient::orderBy('name')->get();
        // Ambil list supplier untuk form pembelian
        $suppliers = \App\Models\Supplier::all();
        return view('inventory.materials', compact('ingredients', 'suppliers'));
    }

    public function production()
    {
        $menus = \App\Models\MenuItem::with('recipe.items.ingredient')->orderBy('name')->get();
        return view('inventory.production', compact('menus'));
    }

    public function storePurchase(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.1',
            'total_price' => 'required|numeric|min:0',
        ]);

        $ingredient = \App\Models\Ingredient::findOrFail($request->ingredient_id);
        
        // 1. Simpan ke Pengeluaran/Pembelian
        $purchase = \App\Models\Purchase::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'supplier_id' => $request->supplier_id,
            'purchase_date' => now()->format('Y-m-d'),
            'total_amount' => $request->total_price,
            'payment_method' => 'Tunai', // Default, bisa diubah di UI nanti
            'notes' => 'Pembelian via Halaman Stok',
        ]);

        \App\Models\PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $request->quantity,
            'unit_price' => $request->total_price / $request->quantity,
            'subtotal' => $request->total_price,
        ]);

        // 2. Hitung Harga Rata-Rata Baru (Moving Average)
        $oldTotalValue = $ingredient->current_stock * $ingredient->cost_per_unit;
        $newTotalValue = $oldTotalValue + $request->total_price;
        $newTotalStock = $ingredient->current_stock + $request->quantity;
        
        $newAvgPrice = $newTotalStock > 0 ? ($newTotalValue / $newTotalStock) : $ingredient->cost_per_unit;

        // 3. Update Stok & Harga
        $ingredient->current_stock = $newTotalStock;
        $ingredient->cost_per_unit = $newAvgPrice;
        $ingredient->save();

        // 4. Update HPP semua menu yang mengandung bahan ini
        foreach ($ingredient->recipeItems as $recipeItem) {
            $recipeItem->recipe->menuItem?->syncHpp();
        }

        return redirect()->back()->with('success', 'Pembelian berhasil! Stok dan HPP telah disesuaikan.');
    }

    public function storeProduction(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'menu_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $menu = \App\Models\MenuItem::with('recipe.items.ingredient')->findOrFail($request->menu_id);
        $qty = $request->quantity;

        // 1. Validasi apakah bahan cukup
        if ($menu->production_capacity < $qty) {
            return redirect()->back()->with('error', 'Bahan mentah tidak cukup untuk memproduksi sejumlah ini!');
        }

        // 2. Potong stok bahan baku
        foreach ($menu->recipe->items as $recipeItem) {
            $ingredient = $recipeItem->ingredient;
            if ($ingredient) {
                $ingredient->current_stock -= ($recipeItem->quantity * $qty);
                $ingredient->save();
            }
        }

        // 3. Tambah stok siap jual
        $menu->current_stock += $qty;
        $menu->save();



        return redirect()->back()->with('success', "Berhasil memproduksi $qty porsi {$menu->name}.");
    }

    public function storeOpname(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'actual_stock' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $ingredient = \App\Models\Ingredient::findOrFail($request->ingredient_id);
        
        // Hitung selisih
        $difference = $request->actual_stock - $ingredient->current_stock;
        
        // Jika minus (bahan terbuang/cacat/miss takaran) -> Catat sebagai kerugian/pengeluaran
        if ($difference < 0) {
            $lossValue = abs($difference) * $ingredient->cost_per_unit;
            \App\Models\Expense::create([
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'expense_date' => now()->format('Y-m-d'),
                'category' => 'Bahan Terbuang/Cacat',
                'amount' => $lossValue,
                'description' => "Penyesuaian stok (Opname): {$ingredient->name}. Alasan: {$request->reason}",
            ]);
        }

        // Update stok fisik
        $ingredient->current_stock = $request->actual_stock;
        $ingredient->save();

        return redirect()->back()->with('success', 'Stok fisik berhasil disesuaikan.');
    }
}
