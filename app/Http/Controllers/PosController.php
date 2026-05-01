<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItem;

class PosController extends Controller
{
    public function index()
    {
        $menus = MenuItem::where('is_active', true)->get();
        return view('pos.index', compact('menus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'buyer_type' => 'required|string',
            'payment_method' => 'required|string',
            'cart' => 'required|string',
        ]);

        $cart = json_decode($request->cart, true);
        if (empty($cart)) {
            return redirect()->back()->with('error', 'Keranjang kosong!');
        }

        $sale = \App\Models\DailySale::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'sale_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'payment_method' => $request->payment_method,
            'total_revenue' => 0,
            'total_hpp' => 0,
            'gross_profit' => 0,
        ]);

        foreach ($cart as $item) {
            $menu = MenuItem::with('recipe.items.ingredient')->find($item['id']);
            if ($menu) {
                \App\Models\DailySaleItem::makeFromQty($sale, $menu, $item['qty'], $request->buyer_type)->save();

                $qtyToSell = $item['qty'];

                // 1. Kurangi stok matang (stok siap jual) dulu jika ada
                if ($menu->current_stock > 0) {
                    if ($menu->current_stock >= $qtyToSell) {
                        $menu->current_stock -= $qtyToSell;
                        $menu->save();
                        $qtyToSell = 0;
                    } else {
                        $qtyToSell -= $menu->current_stock;
                        $menu->current_stock = 0;
                        $menu->save();
                    }
                }

                // 2. Jika stok matang kurang (Made to order), sisa qty potong dari bahan mentah
                if ($qtyToSell > 0 && $menu->recipe) {
                    foreach ($menu->recipe->items as $recipeItem) {
                        $ingredient = $recipeItem->ingredient;
                        if ($ingredient) {
                            $ingredient->current_stock -= ($recipeItem->quantity * $qtyToSell);
                            $ingredient->save();
                        }
                    }
                }
            }
        }

        $sale->recalculateTotals();

        return redirect()->route('pos.index')->with('success', 'Transaksi berhasil disimpan!');
    }
}
