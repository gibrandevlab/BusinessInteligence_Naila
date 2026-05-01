<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\MenuItem;
use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Expense;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User
        $admin = User::create([
            'name' => 'Owner Naila',
            'email' => 'admin@naila.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $kasir = User::create([
            'name' => 'Kasir Naila',
            'email' => 'kasir@naila.com',
            'password' => Hash::make('password'),
            'role' => 'kasir'
        ]);

        // 2. Buat Supplier
        $sup1 = Supplier::create(['name' => 'Pasar Induk', 'kategori_bahan' => 'Sayuran & Bumbu']);
        $sup2 = Supplier::create(['name' => 'Toko Daging Barokah', 'kategori_bahan' => 'Daging']);
        $sup3 = Supplier::create(['name' => 'Grosir Sembako', 'kategori_bahan' => 'Sembako']);

        // 3. Buat Bahan Baku
        $ingBeras = Ingredient::create(['name' => 'Beras Putih', 'unit' => 'kg', 'current_stock' => 50, 'min_stock' => 10, 'cost_per_unit' => 14000, 'supplier_id' => $sup3->id]);
        $ingAyam = Ingredient::create(['name' => 'Daging Ayam', 'unit' => 'kg', 'current_stock' => 20, 'min_stock' => 5, 'cost_per_unit' => 35000, 'supplier_id' => $sup2->id]);
        $ingBawang = Ingredient::create(['name' => 'Bawang Merah', 'unit' => 'gram', 'current_stock' => 5000, 'min_stock' => 1000, 'cost_per_unit' => 40, 'supplier_id' => $sup1->id]);
        $ingKecap = Ingredient::create(['name' => 'Kecap Manis', 'unit' => 'ml', 'current_stock' => 5000, 'min_stock' => 1000, 'cost_per_unit' => 20, 'supplier_id' => $sup3->id]);
        $ingTelur = Ingredient::create(['name' => 'Telur Ayam', 'unit' => 'butir', 'current_stock' => 150, 'min_stock' => 30, 'cost_per_unit' => 2000, 'supplier_id' => $sup3->id]);
        $ingMie = Ingredient::create(['name' => 'Mie Telur', 'unit' => 'bungkus', 'current_stock' => 100, 'min_stock' => 20, 'cost_per_unit' => 5000, 'supplier_id' => $sup3->id]);
        $ingSayur = Ingredient::create(['name' => 'Sawi Hijau', 'unit' => 'gram', 'current_stock' => 3000, 'min_stock' => 500, 'cost_per_unit' => 10, 'supplier_id' => $sup1->id]);

        // 4. Buat Histori Harga Bahan (Biar ngga kosong)
        IngredientPrice::create(['ingredient_id' => $ingBeras->id, 'supplier_id' => $sup3->id, 'price_per_unit' => 14000, 'quantity' => 50, 'purchased_at' => Carbon::now()->subDays(5)]);
        IngredientPrice::create(['ingredient_id' => $ingAyam->id, 'supplier_id' => $sup2->id, 'price_per_unit' => 35000, 'quantity' => 20, 'purchased_at' => Carbon::now()->subDays(2)]);

        // 5. Buat Resep (Kita asumsikan 5 Menu)
        // Menu 1: Nasi Goreng Ayam
        $recNasgor = Recipe::create(['name' => 'Nasi Goreng Ayam', 'serving_qty' => 1, 'packaging_cost' => 1500, 'overhead_cost' => 2000]);
        RecipeItem::create(['recipe_id' => $recNasgor->id, 'ingredient_id' => $ingBeras->id, 'quantity' => 0.2]); // 200g beras
        RecipeItem::create(['recipe_id' => $recNasgor->id, 'ingredient_id' => $ingAyam->id, 'quantity' => 0.05]); // 50g ayam
        RecipeItem::create(['recipe_id' => $recNasgor->id, 'ingredient_id' => $ingTelur->id, 'quantity' => 1]);   // 1 butir telur
        RecipeItem::create(['recipe_id' => $recNasgor->id, 'ingredient_id' => $ingKecap->id, 'quantity' => 15]);  // 15ml kecap
        RecipeItem::create(['recipe_id' => $recNasgor->id, 'ingredient_id' => $ingBawang->id, 'quantity' => 10]); // 10g bawang

        // Menu 2: Mie Goreng Ayam
        $recMie = Recipe::create(['name' => 'Mie Goreng Ayam', 'serving_qty' => 1, 'packaging_cost' => 1500, 'overhead_cost' => 2000]);
        RecipeItem::create(['recipe_id' => $recMie->id, 'ingredient_id' => $ingMie->id, 'quantity' => 1]);       // 1 bungkus mie
        RecipeItem::create(['recipe_id' => $recMie->id, 'ingredient_id' => $ingAyam->id, 'quantity' => 0.05]);    // 50g ayam
        RecipeItem::create(['recipe_id' => $recMie->id, 'ingredient_id' => $ingTelur->id, 'quantity' => 1]);      // 1 butir telur
        RecipeItem::create(['recipe_id' => $recMie->id, 'ingredient_id' => $ingSayur->id, 'quantity' => 50]);     // 50g sayur
        RecipeItem::create(['recipe_id' => $recMie->id, 'ingredient_id' => $ingKecap->id, 'quantity' => 15]);     // 15ml kecap

        // Menu 3: Nasi Ayam Geprek
        $recGeprek = Recipe::create(['name' => 'Nasi Ayam Geprek', 'serving_qty' => 1, 'packaging_cost' => 1500, 'overhead_cost' => 2500]);
        RecipeItem::create(['recipe_id' => $recGeprek->id, 'ingredient_id' => $ingBeras->id, 'quantity' => 0.2]); // 200g beras
        RecipeItem::create(['recipe_id' => $recGeprek->id, 'ingredient_id' => $ingAyam->id, 'quantity' => 0.1]);  // 100g ayam

        // Menu 4: Telur Dadar Spesial
        $recTelur = Recipe::create(['name' => 'Telur Dadar Spesial', 'serving_qty' => 1, 'packaging_cost' => 1000, 'overhead_cost' => 1000]);
        RecipeItem::create(['recipe_id' => $recTelur->id, 'ingredient_id' => $ingTelur->id, 'quantity' => 2]);     // 2 butir telur
        RecipeItem::create(['recipe_id' => $recTelur->id, 'ingredient_id' => $ingBawang->id, 'quantity' => 20]);   // 20g bawang

        // Menu 5: Ayam Bakar Madu (Kita pakai bumbu kecap)
        $recAyamBakar = Recipe::create(['name' => 'Ayam Bakar Manis', 'serving_qty' => 1, 'packaging_cost' => 1500, 'overhead_cost' => 2500]);
        RecipeItem::create(['recipe_id' => $recAyamBakar->id, 'ingredient_id' => $ingAyam->id, 'quantity' => 0.15]); // 150g ayam
        RecipeItem::create(['recipe_id' => $recAyamBakar->id, 'ingredient_id' => $ingKecap->id, 'quantity' => 30]);  // 30ml kecap

        // 6. Buat Menu Item
        $menu1 = MenuItem::create(['recipe_id' => $recNasgor->id, 'name' => 'Nasi Goreng Ayam', 'category' => 'Makanan', 'price_eceran' => 20000, 'price_reseller' => 18000, 'price_agen' => 16000]);
        $menu2 = MenuItem::create(['recipe_id' => $recMie->id, 'name' => 'Mie Goreng Ayam', 'category' => 'Makanan', 'price_eceran' => 18000, 'price_reseller' => 16000, 'price_agen' => 14000]);
        $menu3 = MenuItem::create(['recipe_id' => $recGeprek->id, 'name' => 'Nasi Ayam Geprek', 'category' => 'Makanan', 'price_eceran' => 22000, 'price_reseller' => 20000, 'price_agen' => 18000]);
        $menu4 = MenuItem::create(['recipe_id' => $recTelur->id, 'name' => 'Telur Dadar Spesial', 'category' => 'Lauk', 'price_eceran' => 10000, 'price_reseller' => 9000, 'price_agen' => 8000]);
        $menu5 = MenuItem::create(['recipe_id' => $recAyamBakar->id, 'name' => 'Ayam Bakar Manis', 'category' => 'Lauk', 'price_eceran' => 25000, 'price_reseller' => 22000, 'price_agen' => 20000]);

        // Hitung HPP awal
        foreach ([$menu1, $menu2, $menu3, $menu4, $menu5] as $menu) {
            $menu->syncHpp();
        }

        // 7. Dummy Penjualan Harian (3 Hari Terakhir)
        for ($i = 2; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sale = DailySale::create([
                'user_id' => $kasir->id,
                'sale_date' => $date->format('Y-m-d')
            ]);

            // Random qty sold & buyer type
            $buyerTypes = ['Eceran', 'Reseller', 'Agen'];
            DailySaleItem::makeFromQty($sale, $menu1, rand(10, 30), $buyerTypes[array_rand($buyerTypes)])->save();
            DailySaleItem::makeFromQty($sale, $menu2, rand(5, 20), $buyerTypes[array_rand($buyerTypes)])->save();
            DailySaleItem::makeFromQty($sale, $menu3, rand(15, 40), $buyerTypes[array_rand($buyerTypes)])->save();
            DailySaleItem::makeFromQty($sale, $menu4, rand(5, 10), $buyerTypes[array_rand($buyerTypes)])->save();
            DailySaleItem::makeFromQty($sale, $menu5, rand(8, 25), $buyerTypes[array_rand($buyerTypes)])->save();

            $sale->recalculateTotals();

            // 8. Dummy Pembelian Bahan Baku (Purchase)
            $purchase = Purchase::create([
                'user_id' => $admin->id,
                'supplier_id' => $sup1->id,
                'purchase_date' => $date->format('Y-m-d'),
                'payment_method' => 'Tunai',
                'notes' => 'Belanja rutin harian'
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'ingredient_id' => $ingBawang->id,
                'quantity' => 1000,
                'price_per_unit' => 40,
                'subtotal' => 40000
            ]);

            $purchase->recalculateTotal();

            // 9. Dummy Pengeluaran Operasional (Expense)
            Expense::create([
                'user_id' => $admin->id,
                'category' => 'Biaya Produksi',
                'description' => 'Biaya gas dan tenaga masak harian',
                'amount' => 50000,
                'expense_date' => $date->format('Y-m-d')
            ]);
        }
    }
}
