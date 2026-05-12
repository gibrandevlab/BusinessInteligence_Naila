# BAB IV: PERANCANGAN SISTEM BUSINESS INTELLIGENCE DENGAN METODOLOGI CRISP-DM

## IV.1 Data Understanding (Pemahaman Data)

### IV.1.1 Identifikasi Sumber Data Primer

Sistem Aplikasi Produksi Naila mengintegrasikan data operasional dari lima modul utama yang membentuk keseluruhan ekosistem UMKM pangan. Setiap modul menghasilkan tabel terstruktur yang menjadi fondasi analisis data:

#### A. Modul Penjualan (Sales Module)
Tabel penjualan mencakup dua level agregasi:

**Level Header: `daily_sales` (Migration: 2026_05_01_063330_b_create_daily_sales_table.php)**
- **Atribut utama**: `id`, `user_id`, `sale_date`, `total_revenue`, `total_hpp`, `gross_profit`, `payment_method`, `notes`
- **Tipe data**: Decimal(12,2) untuk financial metrics, Date untuk temporal dimension
- **Kardinalitas**: Satu record per hari (constraint: unique sale_date)
- **Referensi**: Foreign key ke tabel `users` (who, user_id), pencatatan transaksi level hari

**Level Detail: `daily_sale_items` (Migration: 2026_05_01_063331_a_create_daily_sale_items_table.php)**
- **Atribut utama**: `id`, `daily_sale_id`, `menu_item_id`, `qty_sold`, `selling_price`, `hpp_per_item`, `subtotal_revenue`, `subtotal_hpp`, `contribution_margin`
- **Fitur khusus**: Snapshot harga pada waktu transaksi (column `selling_price`, `hpp_per_item` tidak update retroaktif)
- **Atribut derivatif**: `contribution_margin = subtotal_revenue - subtotal_hpp` (calculated at insertion)
- **Buyer segmentation**: Column `buyer_type` (Eceran, Reseller, Agen) untuk multi-channel analysis

Logika pengisian diatur dalam Model `DailySaleItem::makeFromQty()` — fungsi factory yang mengotomasi perhitungan subtotal berdasarkan formula:
$$\text{contribution\_margin} = \text{qty\_sold} \times (\text{selling\_price} - \text{hpp\_per\_item})$$

#### B. Modul Inventory & Bahan Baku (Ingredient Module)
**Tabel `ingredients` (Migration: 2026_05_01_063328_a_create_ingredients_table.php)**
- **Atribut utama**: `id`, `name`, `unit`, `current_stock`, `min_stock`, `cost_per_unit`, `supplier_id`, `is_active`
- **Dimensi analitik**: 
  - `cost_per_unit` (Decimal 10,2): Harga per unit bahan, diperbarui secara otomatis menggunakan Moving Average
  - `current_stock` (Decimal 10,2): Stok terkini (real-time inventory position)
  - `min_stock` (Decimal 10,2): Alert threshold untuk procurement
- **Relasi**: Foreign key ke `suppliers`, relasi One-to-Many ke `recipe_items` dan `purchase_items`

**Tabel `ingredient_prices` (Migration: 2026_05_01_063328_b_create_ingredient_prices_table.php)**
- **Purpose**: Historical tracking perubahan harga bahan dari setiap supplier
- **Atribut**: `ingredient_id`, `supplier_id`, `price`, `effective_date`, `notes`
- **Kegunaan BI**: Price trend analysis, supplier comparison, cost escalation prediction

#### C. Modul Resep & Menu (Recipe & MenuItem Module)
**Tabel `recipes` (Migration: 2026_05_01_063329_a_create_recipes_table.php)**
- **Atribut utama**: `id`, `name`, `description`, `serving_qty`, `packaging_cost`, `overhead_cost`, `timestamps`
- **Struktur Bill of Materials (BOM)**: Resep menyimpan metadata produksi (berapa porsi per batch, overhead produksi)

**Tabel `recipe_items` (Migration: 2026_05_01_063329_b_create_recipe_items_table.php)**
- **Atribut utama**: `id`, `recipe_id`, `ingredient_id`, `quantity` (unit sesuai ingredient)
- **Computed attribute** (Model `RecipeItem::getCostAttribute()` ): 
$$\text{cost\_per\_item} = \text{quantity} \times \text{ingredient.cost\_per\_unit}$$

**Tabel `menu_items` (Migration: 2026_05_01_063330_a_create_menu_items_table.php)**
- **Atribut utama**: `id`, `recipe_id`, `name`, `category`, `price_eceran`, `price_reseller`, `price_agen`, `hpp`, `current_stock`, `is_active`
- **Fitur multi-tier pricing**: Tiga price point untuk berbagai segmen customer (retail, reseller, agent)
- **HPP (Harga Pokok Penjualan)**: Calculated field yang diperbarui otomatis via method `syncHpp()` ketika ada perubahan ingredient cost

#### D. Modul Pembelian (Purchase Module)
**Tabel `purchases` (Migration: 2026_05_01_063331_b_create_purchases_table.php)**
- **Atribut utama**: `id`, `user_id`, `supplier_id`, `purchase_date`, `total_amount`, `payment_method`, `notes`
- **Struktur**: Header-detail pattern (relasi 1-M ke `purchase_items`)

**Tabel `purchase_items` (Migration: 2026_05_01_063331_c_create_purchase_items_table.php)**
- **Atribut utama**: `purchase_id`, `ingredient_id`, `quantity`, `unit_price`, `subtotal`
- **Cross-link**: Foreign key ke `ingredients` untuk tracking pembelian per bahan

#### E. Modul Pengeluaran Operasional (Expense Module)
**Tabel `expenses` (Migration: 2026_05_01_063332_create_expenses_table.php)**
- **Atribut utama**: `id`, `user_id`, `category`, `description`, `amount`, `expense_date`, `notes`
- **Category enumeration**: Operasional, Gaji, Utilitas, Marketing, Lainnya (strings tanpa constraint, flexible untuk domain UMKM)
- **Temporal**: `expense_date` (Date) untuk time-series analysis pengeluaran

#### F. Modul Log Produksi (Production Log Module)
**Tabel `production_logs` (Migration: 2026_05_10_110703_create_production_logs_table.php)**
- **Atribut utama**: `id`, `menu_item_id`, `quantity`, `production_date`
- **Purpose**: Recorded setiap kali produksi dilakukan (melalui `InventoryController::storeProduction()`)
- **Kegunaan BI**: Production efficiency analysis, waste tracking (gap antara production dan actual sales)

---

### IV.1.2 Karakteristik & Dimensionalitas Data untuk Analisis

Berdasarkan struktur tabel di atas, sistem BI dibangun atas empat dimensi utama:

| Dimensi | Definisi | Sumber Data | Contoh Atribut |
|---------|----------|------------|----------------|
| **Time** | Temporal dimension untuk trend dan time-series analysis | `daily_sales.sale_date`, `purchases.purchase_date`, `production_logs.production_date` | Hari, Minggu, Bulan, Tahun |
| **Product** | Dimensi produk (menu) dengan hierarchical kategorisasi | `menu_items.name`, `menu_items.category`, `menu_items.recipe_id` | Kategori (Makanan/Minuman/Snack), Resep yang digunakan |
| **Channel** | Segmentasi customer berdasarkan buyer type | `daily_sale_items.buyer_type` | Eceran, Reseller, Agen |
| **Organization** | User/operator yang melakukan transaksi | `daily_sales.user_id`, `purchases.user_id` | Kasir, Inventory Manager, Admin |

Atribut financial yang menjadi Key Performance Indicator (KPI) utama (dalam backticks untuk kode database):

| KPI | Formula | Sumber | Interpretasi |
|-----|---------|--------|--------------|
| **Revenue** | `SUM(daily_sale_items.subtotal_revenue)` | daily sales → daily sale items | Total pendapatan penjualan |
| **HPP (COGS)** | `SUM(daily_sale_items.subtotal_hpp)` | daily sales → daily sale items | Total biaya produksi produk terjual |
| **Gross Profit** | Revenue \- HPP | daily sales.gross profit | Laba kotor sebelum biaya operasional |
| **Contribution Margin** | `daily_sale_items.contribution_margin` per item | daily sale items | Kontribusi keuntungan per produk |
| **Food Cost %** | (HPP / Revenue) × 100% | DashboardController formula | Persentase biaya bahan vs penjualan |
| **Pengeluaran Operasional** | `SUM(expenses.amount)` | expenses | Biaya non-COGS (gaji, listrik, dll) |
| **Net Profit** | Gross Profit \- Pengeluaran Operasional | DashboardController formula | Laba bersih bisnis |

---

## IV.2 Data Preparation (ETL Implisit & Data Cleaning)

### IV.2.1 Proses Extract (Data Extraction)

Dalam sistem Aplikasi Produksi Naila, ekstraksi data dilakukan secara **real-time event-driven** tanpa batch processing eksplisit. Setiap transaksi langsung disimpan ke database melalui Controller-layer API.

#### A. Extract dari Penjualan (PosController::store)
**File**: [app/Http/Controllers/PosController.php](app/Http/Controllers/PosController.php)

Proses ekstraksi data penjualan dimulai ketika kasir menginput transaksi:

```
1. Input User: Cart JSON (array of {id, qty})
2. Validasi: cart tidak boleh kosong
3. Ekstraksi Data dari Form:
   - buyer_type (Eceran/Reseller/Agen)
   - payment_method 
   - cart items (menu_item_id, quantity)
   - current timestamp (sale_date = Carbon::now())
4. Load Related Data:
   - MenuItem::with('recipe.items.ingredient') — fetch HPP dependencies
```

Kode ekstraksi:
```php
$cart = json_decode($request->cart, true); // Extract dari JSON POST
$sale = \App\Models\DailySale::create([
    'user_id' => Auth::id(),
    'sale_date' => Carbon::now()->format('Y-m-d'),
    'payment_method' => $request->payment_method,
]);

foreach ($cart as $item) {
    $menu = MenuItem::with('recipe.items.ingredient')->find($item['id']); // Extract menu + ingredient
```

**Data Extraction Points**:
1. Per-item selling price diambil dari `MenuItem` berdasarkan buyer_type (price_eceran / price_reseller / price_agen)
2. HPP per-item diambil dari `MenuItem::$hpp` (snapshot saat transaksi)
3. Stok (raw & finished goods) di-extract dari `MenuItem::$current_stock` dan `Ingredient::$current_stock`

#### B. Extract dari Pembelian (InventoryController::storePurchase)
**File**: [app/Http/Controllers/InventoryController.php](app/Http/Controllers/InventoryController.php)

```
1. Input User: ingredient_id, supplier_id, quantity, total_price
2. Validasi: quantity > 0, ingredient exists
3. Ekstraksi & Kalkulasi:
   - Old Total Value = current_stock × cost_per_unit
   - Beli Quantity & Total Price dari form user
```

#### C. Extract dari Produksi (InventoryController::storeProduction)
```
1. Input User: menu_id, quantity
2. Extract menu dengan relasi: with('recipe.items.ingredient')
3. Load: production_capacity (derived attribute untuk validasi stok cukup)
4. Catat ke ProductionLog
```

#### D. Extract dari Dashboard Aggregation (DashboardController::index)
**File**: [app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php)

Dashboard melakukan **extract-on-demand** dengan period filtering:

```php
$period = $request->query('period', 'today'); // Default: hari ini

if ($period === 'today') {
    $date = Carbon::today();
    $querySales->whereDate('sale_date', $date);
    $queryPurchases->whereDate('purchase_date', $date);
    $queryExpenses->whereDate('expense_date', $date);
} elseif ($period === 'this_week') {
    $start = Carbon::now()->startOfWeek();
    $end = Carbon::now()->endOfWeek();
    $querySales->whereBetween('sale_date', [$start, $end]);
    // ... same for purchases, expenses
} elseif ($period === 'this_month') {
    // ... monthly aggregation
}

$sales = $querySales->get(); // Execute extraction
```

**Extract Pattern**: 
- Temporal filtering pada query level (WHERE clause)
- Eager loading relasi yang diperlukan
- Agregasi di PHP memory (collection operations)

---

### IV.2.2 Proses Transform (Data Transformation & Business Logic)

Transformasi data melibatkan kalkulasi matematika yang secara otomatis dilakukan pada insertion/update untuk memastikan data consistency.

#### A. Transform 1: Kalkulasi HPP Produk (Harga Pokok Penjualan)

**Lokasi Logika**: [app/Models/Recipe.php](app/Models/Recipe.php) method `calculateHpp()`

Rumus HPP per porsi:
$$\text{HPP per porsi} = \frac{\sum (\text{ingredient quantity} \times \text{ingredient cost per unit})}{\text{serving qty}} + \text{packaging cost} + \text{overhead cost}$$

**Implementasi**:
```php
// File: Recipe.php
public function calculateHpp(): float
{
    $totalBahan = 0;
    foreach ($this->items as $item) {
        $ingredient = $item->ingredient;
        // cost_per_unit adalah harga per 1 unit (gram/ml/pcs)
        $totalBahan += $item->quantity * $ingredient->cost_per_unit;
    }
    
    $hppPerPorsi = ($totalBahan / max($this->serving_qty, 1)) + 
                   $this->packaging_cost + 
                   $this->overhead_cost;
    return round($hppPerPorsi, 2);
}
```

**Trigger**: Dijalankan setiap kali ada perubahan ingredient cost (via `InventoryController::storePurchase()`)

**Data Quality Check**: Validasi serving_qty > 0 (denominator guard) untuk mencegah division by zero.

---

#### B. Transform 2: Kalkulasi Contribution Margin Per Item Penjualan

**Lokasi Logika**: [app/Models/DailySaleItem.php](app/Models/DailySaleItem.php) method `makeFromQty()` dan [app/Http/Controllers/PosController.php](app/Http/Controllers/PosController.php)

Rumus:
$$\text{subtotal\_revenue} = \text{qty\_sold} \times \text{selling\_price}$$
$$\text{subtotal\_hpp} = \text{qty\_sold} \times \text{hpp\_per\_item}$$
$$\text{contribution\_margin} = \text{subtotal\_revenue} - \text{subtotal\_hpp}$$

**Implementasi** (Factory Method):
```php
// DailySaleItem.php
public static function makeFromQty(DailySale $sale, MenuItem $menu, int $qty, string $buyerType = 'Eceran'): self
{
    $sellingPrice = $menu->price_eceran;
    if ($buyerType === 'Reseller') {
        $sellingPrice = $menu->price_reseller ?: $menu->price_eceran;
    } elseif ($buyerType === 'Agen') {
        $sellingPrice = $menu->price_agen ?: $menu->price_eceran;
    }

    $subtotalRevenue = $qty * $sellingPrice;
    $subtotalHpp     = $qty * $menu->hpp;

    return new self([
        'qty_sold'           => $qty,
        'selling_price'      => $sellingPrice,
        'hpp_per_item'       => $menu->hpp,
        'subtotal_revenue'   => $subtotalRevenue,
        'subtotal_hpp'       => $subtotalHpp,
        'contribution_margin'=> $subtotalRevenue - $subtotalHpp, // TRANSFORM HAPPEN HERE
    ]);
}
```

**Data Quality**: Nilai negative selling_price atau hpp akan tertangkap di validation layer (request validation di PosController).

---

#### C. Transform 3: Aggregasi Daily Sales Header

**Lokasi Logika**: [app/Models/DailySale.php](app/Models/DailySale.php) method `recalculateTotals()`

Proses aggregasi dari detail items ke header:
$$\text{total\_revenue} = \sum (\text{daily\_sale\_items.subtotal\_revenue})$$
$$\text{total\_hpp} = \sum (\text{daily\_sale\_items.subtotal\_hpp})$$
$$\text{gross\_profit} = \text{total\_revenue} - \text{total\_hpp}$$

**Implementasi**:
```php
// DailySale.php
public function recalculateTotals(): void
{
    $this->load('items');
    $this->total_revenue = $this->items->sum('subtotal_revenue');
    $this->total_hpp     = $this->items->sum('subtotal_hpp');
    $this->gross_profit  = $this->total_revenue - $this->total_hpp;
    $this->save();
}
```

**Trigger**: Dipanggil di `PosController::store()` setelah semua items diinsert.

**Consistency Check**: Sum dari items harus equal dengan header totals (validated post-transaction).

---

#### D. Transform 4: Moving Average Cost of Ingredient

**Lokasi Logika**: [app/Http/Controllers/InventoryController.php](app/Http/Controllers/InventoryController.php) method `storePurchase()`

Metode **Weighted Average** digunakan untuk update cost_per_unit:
$$\text{Old Total Value} = \text{current\_stock} \times \text{cost\_per\_unit}_{\text{old}}$$
$$\text{New Total Value} = \text{Old Total Value} + \text{Purchase Price}$$
$$\text{New Total Stock} = \text{current\_stock} + \text{Purchase Quantity}$$
$$\text{cost\_per\_unit}_{\text{new}} = \frac{\text{New Total Value}}{\text{New Total Stock}}$$

**Implementasi**:
```php
// InventoryController.php storePurchase()
$oldTotalValue = $ingredient->current_stock * $ingredient->cost_per_unit;
$newTotalValue = $oldTotalValue + $request->total_price;
$newTotalStock = $ingredient->current_stock + $request->quantity;

$newAvgPrice = $newTotalStock > 0 ? ($newTotalValue / $newTotalStock) : $ingredient->cost_per_unit;

$ingredient->current_stock = $newTotalStock;
$ingredient->cost_per_unit = $newAvgPrice;
$ingredient->save();
```

**Cascade Update**: Setelah ingredient cost diupdate, semua menu yang menggunakan ingredient ini harus di-recalculate HPP-nya:
```php
foreach ($ingredient->recipeItems as $recipeItem) {
    $recipeItem->recipe->menuItem?->syncHpp();
}
```

---

#### E. Transform 5: Kalkulasi Food Cost Percentage

**Lokasi Logika**: [app/Http/Controllers/DashboardController.php](app/Http/Controllers/DashboardController.php)

$$\text{Food Cost \%} = \frac{\text{total\_hpp}}{\text{total\_revenue}} \times 100\%$$

**Implementasi**:
```php
$kpi = [
    'revenue'    => $totalRev,
    'profit'     => $totalProfit,
    'fc_percent' => $totalRev > 0 ? round(($totalHpp / $totalRev) * 100, 2) : 0,
];
```

**Safeguard**: Division-by-zero check (jika revenue = 0, return 0% bukan infinity).

---

### IV.2.3 Data Cleaning & Validasi Integritas

Data cleaning dilakukan pada dua tahap: **input validation** dan **operational validation**.

#### A. Input Validation (Request Level)

Setiap endpoint Controller mengimplementasikan `$request->validate()` untuk membrane antara user input dan database:

**PosController::store()**
```php
$request->validate([
    'buyer_type'     => 'required|string',
    'payment_method' => 'required|string',
    'cart'           => 'required|string', // JSON validation at presentation layer
]);

$cart = json_decode($request->cart, true);
if (empty($cart)) {
    return redirect()->back()->with('error', 'Keranjang kosong!');
}
```

**InventoryController::storePurchase()**
```php
$request->validate([
    'ingredient_id' => 'required|exists:ingredients,id',  // Referential integrity
    'supplier_id'   => 'nullable|exists:suppliers,id',     // Optional FK
    'quantity'      => 'required|numeric|min:0.1',         // Type & range check
    'total_price'   => 'required|numeric|min:0',           // No negative prices
]);
```

**InventoryController::storeProduction()**
```php
$request->validate([
    'menu_id'  => 'required|exists:menu_items,id',
    'quantity' => 'required|integer|min:1', // Positive integers only
]);
```

#### B. Business Logic Validation

Selain input validation, terdapat validasi bisnis untuk memastikan transaksi logis:

**Stock Sufficiency Check** (InventoryController::storeProduction):
```php
if ($menu->production_capacity < $qty) {
    return redirect()->back()->with('error', 'Bahan mentah tidak cukup untuk memproduksi sejumlah ini!');
}
```

Atribut `production_capacity` di-compute dari ingredient stock vs recipe requirements.

**Cost Consistency Check** (InventoryController::storeOpname):
```php
$difference = $request->actual_stock - $ingredient->current_stock;

if ($difference < 0) {
    // Kerugian material tercatat sebagai Expense
    $lossValue = abs($difference) * $ingredient->cost_per_unit;
    Expense::create([...]);
}
```

Sistem mencatat discrepancy stok sebagai expense (waste tracking) — fitur data quality yang penting untuk BI.

---

#### C. Temporal Consistency (Date Validation)

Semua tanggal disimpan dalam format `DATE` dan tidak boleh di masa depan (implicit dalam business logic):
```php
'purchase_date' => now()->format('Y-m-d'),
'production_date' => now()->format('Y-m-d'),
```

#### D. Referential Integrity (Foreign Keys)

Database schema menggunakan `foreignId()` constraints untuk memastikan data integrity:
```php
// daily_sales table
$table->foreignId('user_id')->constrained()->cascadeOnDelete();

// daily_sale_items table
$table->foreignId('daily_sale_id')->constrained()->cascadeOnDelete();
$table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
```

Cascade delete policy memastikan orphaned records tidak tersisa.

---

## IV.3 Data Modelling (Pemodelan Data untuk Analisis)

### IV.3.1 Dimensional Model: Fact Table & Dimension Tables

Meskipun aplikasi menggunakan **operational OLTP schema**, sistem BI mengakses data melalui dimensional model implisit:

#### Fact Tables

**Fact Table: daily_sale_items** (granularity: per product per day per channel)
| Dimensi | Tabel Referensi | Atribut |
|---------|-----------------|---------|
| Time    | Implicit        | sale_date (dari daily_sales) |
| Product | menu_items      | menu_item_id, name, category, recipe_id |
| Channel | Implicit        | buyer_type (Eceran/Reseller/Agen) |
| Organization | users      | user_id (kasir yang menginput) |

**Measures** (numeric facts):
- `qty_sold` (quantity)
- `subtotal_revenue` (revenue)
- `subtotal_hpp` (COGS)
- `contribution_margin` (profit at line-item level)

**Fact Table: production_logs** (granularity: per product per production day)
| Atribut | Tipe | Purpose |
|---------|------|---------|
| menu_item_id | FK | Product dimension |
| quantity | INT | Production volume |
| production_date | DATE | Time dimension |

---

#### Dimension Tables

**Dimension: Time** (Calendar hierarchy)
- Source: Daily_sales.sale_date, purchase.purchase_date, production_logs.production_date
- Granularity: Daily
- Hierarchy: Day → Week → Month → Year

**Dimension: Product (Menu)** 
- Table: menu_items, recipes, recipe_items
- Attributes: name, category, hpp, price_eceran, price_reseller, price_agen
- Hierarchy: Category → Menu Item → Recipe

**Dimension: Channel**
- Source: daily_sale_items.buyer_type
- Values: {Eceran, Reseller, Agen}

**Dimension: Ingredient (for Supply Chain Analysis)**
- Table: ingredients
- Attributes: name, unit, supplier_id, cost_per_unit, current_stock
- Support: Waste analysis, procurement optimization

---

### IV.3.2 Klasifikasi BCG Matrix: Implementasi Decision Tree

**File Referensi**: [app/Http/Controllers/AnalysisController.php](app/Http/Controllers/AnalysisController.php) method `runClassification()` dan [app/Http/Controllers/SpkController.php](app/Http/Controllers/SpkController.php) method `index()`

#### A. Metrik Klasifikasi

Sistem menggunakan dua dimensi BCG untuk klasifikasi menu:

**Dimensi 1: Menu Mix (Popularitas/Market Share) — MM%**
$$\text{MM\%} = \frac{\text{qty\_sold\_menu}}{\text{total\_qty\_all}} \times 100\%$$

Threshold:
$$\text{Average MM} = \frac{1}{N} \times 0.7 \times 100\% = \frac{70}{N}\%$$
dimana N = jumlah menu item yang terjual.

Interpretasi:
- **MM ≥ Average MM**: High Market Share ("Market Leader")
- **MM < Average MM**: Low Market Share ("Niche Product")

**Dimensi 2: Contribution Margin (Profitabilitas) — CM**
$$\text{CM per item} = \frac{\text{total\_contribution\_margin\_menu}}{\text{qty\_sold\_menu}}$$

$$\text{Average CM} = \frac{\text{total\_contribution\_margin\_all}}{\text{total\_qty\_all}}$$

Interpretasi:
- **CM ≥ Average CM**: High Profit ("High Margin")
- **CM < Average CM**: Low Profit ("Low Margin")

---

#### B. Matriks Klasifikasi

Kombinasi kedua dimensi menghasilkan empat kategori BCG:

| MM vs CM | CM ≥ Avg | CM < Avg |
|----------|----------|----------|
| **MM ≥ Avg** | **STAR** 🌟 | **PLOWHORSE** 🐴 |
| **MM < Avg** | **PUZZLE** 🧩 | **DOG** 🐕 |

**Kode Implementasi** (SpkController::index):
```php
if ($mmPercent >= $averageMM && $cmPerItem >= $averageCM) {
    $category = 'Star';
    $action = 'Pertahankan kualitas & jadikan signature dish.';
} elseif ($mmPercent >= $averageMM && $cmPerItem < $averageCM) {
    $category = 'Plowhorse';
    $action = 'Kurangi porsi sedikit atau naikkan harga secara bertahap.';
} elseif ($mmPercent < $averageMM && $cmPerItem >= $averageCM) {
    $category = 'Puzzle';
    $action = 'Promosikan lebih gencar (bundling/diskon).';
} else {
    $category = 'Dog';
    $action = 'Evaluasi resep atau hapus dari menu jika tidak prospek.';
}
```

#### C. Kategori & Rekomendasi Tindakan

| Kategori | MM% | CM | Karakteristik | Rekomendasi Strategis |
|----------|-----|----|--------------------|----------------------|
| **Star** | ↑ | ↑ | Best seller dengan margin tinggi | Pertahankan kualitas; jadikan signature; tingkatkan promosi |
| **Plowhorse** | ↑ | ↓ | High volume, low margin | Naikkan harga bertahap atau kurangi porsi; optimasi cost |
| **Puzzle** | ↓ | ↑ | Low volume, high margin | Gencar promosi; bundling; discount strategis untuk acquire volume |
| **Dog** | ↓ | ↓ | Slow mover, low profit | Evaluasi resep/cost; hapus jika tidak prospek; rebrand |

---

### IV.3.3 Prediksi Penjualan: Linear Regression Model

**File Referensi**: [app/Http/Controllers/AnalysisController.php](app/Http/Controllers/AnalysisController.php) method `runRegression()`

#### A. Model Matematika

Sistem mengimplementasikan **Simple Linear Regression** dengan formula:
$$\hat{y} = a + bx$$

dimana:
- $x$ = urutan hari (1, 2, 3, ...)
- $y$ = qty_sold pada hari tersebut
- $a$ = intercept (baseline volume)
- $b$ = slope (trend per hari)

#### B. Parameter Estimasi (Least Squares Method)

$$b = \frac{n \sum xy - \sum x \sum y}{n \sum x^2 - (\sum x)^2}$$

$$a = \bar{y} - b \bar{x}$$

**Implementasi Kode**:
```php
$n = $dailyData->count(); // Jumlah hari observasi
$xValues = range(1, $n);  // Sequence [1, 2, 3, ..., n]
$yValues = $dailyData->pluck('daily_qty')->toArray(); // Observed sales

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
```

#### C. Prediksi (Forecast)

Untuk $k$ hari ke depan:
$$\hat{y}_{n+k} = a + b(n + k)$$

Dengan validasi $\hat{y} \geq 0$ (tidak ada negative forecast):

```php
$nextDayPredictions = [];
for ($day = 1; $day <= 3; $day++) {
    $predictedX = $n + $day;
    $predictedY = max(0, round($a + ($b * $predictedX)));
    $nextDayPredictions[] = [
        'day_label' => ['Besok', 'Lusa', 'Besoknya lagi'][$day-1],
        'predicted_qty' => $predictedY
    ];
}
```

**Interpretasi Slope**:
- **$b > 0$**: Trend naik (penjualan meningkat)
- **$b < 0$**: Trend turun (penjualan menurun)
- **$b ≈ 0$**: Trend flat/stabil

#### D. Reliability Indicator (Data Confidence Score)

**File**: AnalysisController::calculateDataScore()

Sistem mengevaluasi kualitas data sebelum memberikan prediksi:

$$\text{Overall Score} = \frac{\text{dayScore} + \text{recordScore} + \text{menuScore}}{3}$$

dimana:
- **dayScore** = $\min(\frac{\text{distinct days}}{30}, 1) \times 100$ (target: 30 hari)
- **recordScore** = $\min(\frac{\text{total records}}{100}, 1) \times 100$ (target: 100 transaksi)
- **menuScore** = $\min(\frac{\text{distinct menus}}{3}, 1) \times 100$ (target: 3 produk)

**Klasifikasi Confidence**:
- **≥ 70%**: "Cukup Baik" → Prediksi dapat dipercaya
- **40-70%**: "Sedang" → Prediksi perlu cross-validation dengan expert judgment
- **< 40%**: "Masih Sedikit" → Prediksi masih "learning phase", akurasi tidak guarantee

**Implementasi**:
```php
private function calculateDataScore(): array
{
    $days = DailySale::select('sale_date')->distinct()->count();
    $records = DailySaleItem::count();
    $menus = MenuItem::count();

    $dayScore = min(($days / 30) * 100, 100);
    $recordScore = min(($records / 100) * 100, 100);
    $menuScore = min(($menus / 3) * 100, 100);

    $overall = round(($dayScore + $recordScore + $menuScore) / 3);

    if ($overall >= 70) {
        $level = 'good';
        $message = 'Sistem sudah memiliki cukup data untuk mulai mengenali pola penjualan Anda.';
    } elseif ($overall >= 40) {
        $level = 'moderate';
        $message = 'Tebakan AI masih bisa meleset. Terus gunakan aplikasi untuk memperbanyak data.';
    } else {
        $level = 'low';
        $message = 'Akurasinya belum bisa dijadikan patokan utama. Tebakan akan semakin akurat setelah data terkumpul.';
    }

    return compact('overall', 'level', 'message', '...');
}
```

---

### IV.3.4 Early Warning System: Decision Tree untuk Prediksi Trend

**File Referensi**: [app/Http/Controllers/AnalysisController.php](app/Http/Controllers/AnalysisController.php) method `runClassification()`

#### A. Trend Calculation

Menggunakan **simple trend detection** dengan membagi data penjualan menjadi dua periode:

$$\text{First Half Qty} = \sum_{i=1}^{\lfloor n/2 \rfloor} \text{qty\_sold}_i$$

$$\text{Second Half Qty} = \sum_{i=\lceil n/2 \rceil+1}^{n} \text{qty\_sold}_i$$

**Trend Rules**:
- **Naik**: Jika Second Half > First Half × 1.1 (peningkatan > 10%)
- **Turun**: Jika Second Half < First Half × 0.9 (penurunan > 10%)
- **Stabil**: Selainnya

**Implementasi**:
```php
$halfCount = floor($sales->count() / 2);
if ($halfCount > 0) {
    $firstHalfQty = $sales->take($halfCount)->sum('qty_sold');
    $secondHalfQty = $sales->skip($halfCount)->sum('qty_sold');

    if ($secondHalfQty > $firstHalfQty * 1.1) {
        $trend = 'naik';
    } elseif ($secondHalfQty < $firstHalfQty * 0.9) {
        $trend = 'turun';
    } else {
        $trend = 'stabil';
    }
}
```

#### B. Decision Tree: BCG Category + Trend → Future Prediction

Sistem menggunakan kombinasi **current BCG category** dan **trend** untuk memprediksi kategori masa depan:

```
IF Current = Star:
    IF Trend = Turun:
        → "Berisiko Turun Menjadi Dog/Plowhorse" (confidence: 85%)
        → Action: Inovasi segar atau promo
    ELSE:
        → "Aman di Posisi Puncak" (confidence: 95%)
        → Action: Pertahankan

IF Current = Plowhorse:
    IF Trend = Naik AND CM > 0.8 × Average CM:
        → "Berpotensi Naik Menjadi Star" (confidence: 80%)
        → Action: Coba naikkan harga jual
    ELSEIF Trend = Turun:
        → "Berisiko Turun Menjadi Dog" (confidence: 85%)
        → Action: Optimasi cost atau rebrand
    ELSE:
        → "Stabil sebagai andalan volume" (confidence: 90%)

IF Current = Puzzle:
    IF Trend = Naik:
        → "Berpotensi Naik Menjadi Star" (confidence: 80%)
        → Action: Genjot pemasaran
    ELSE:
        → "Tertahan di Puzzle" (confidence: 75%)
        → Action: Evaluasi strategi positioning

IF Current = Dog:
    IF Trend = Turun:
        → "Kritis (Dead Stock)" (confidence: 90%)
        → Action: Hapus dari menu
    ELSEIF Trend = Naik:
        → "Tanda Kebangkitan" (confidence: 70%)
        → Action: Monitor terus
    ELSE:
        → "Tetap Dog" (confidence: 85%)
        → Action: Hapus atau rebrand
```

**Implementasi** (Simplified):
```php
$futurePrediction = '';
$confidence = 0;

if ($currentCategory === 'Star') {
    if ($trend === 'turun') {
        $futurePrediction = 'Berisiko Turun Menjadi Dog/Plowhorse.';
        $confidence = 85;
    } else {
        $futurePrediction = 'Aman di Posisi Puncak.';
        $confidence = 95;
    }
} elseif ($currentCategory === 'Plowhorse') {
    // ... similar logic
}

return [
    'future_prediction' => $futurePrediction,
    'confidence' => $confidence
];
```

---

## IV.4 Evaluation (Evaluasi & Validasi Hasil Analisis)

### IV.4.1 Validasi Intra-System (Consistency Checks)

Sistem melakukan cross-validation antara berbagai komponen untuk memastikan data integrity dan logika bisnis konsisten.

#### A. Daily Sales Balance Validation

Setiap hari harus memiliki **zero-sum balance** antara items dan header:

$$\text{daily\_sales.total\_revenue} = \sum (\text{daily\_sale\_items.subtotal\_revenue})$$

$$\text{daily\_sales.total\_hpp} = \sum (\text{daily\_sale\_items.subtotal\_hpp})$$

$$\text{daily\_sales.gross\_profit} = \text{total\_revenue} - \text{total\_hpp}$$

**Implementasi** (Post-transaction):
```php
// PosController::store()
$sale->recalculateTotals(); // Recalculate dan verify
```

Jika ada discrepancy, ada bug dalam transaction processing.

#### B. Inventory Flow Validation

**Raw Materials**:
$$\text{Ingredient.current\_stock}_{\text{final}} = \text{initial} + \text{purchases} - \text{production\_usage} - \text{waste}$$

**Finished Goods**:
$$\text{MenuItem.current\_stock}_{\text{final}} = \text{initial} + \text{production} - \text{sales}$$

**Logika di Code**:
- Setiap pembelian ingredient → `current_stock` bertambah (InventoryController::storePurchase)
- Setiap produksi → ingredient berkurang, MenuItem bertambah (InventoryController::storeProduction)
- Setiap penjualan → MenuItem berkurang (PosController::store)
- Setiap opname → adjust dengan mencatat waste sebagai Expense

#### C. Cost Consistency Validation

**HPP Propagation Check**:
$$\text{Setiap MenuItem.hpp harus} = \text{Recipe.calculateHpp()}$$

Trigger: Setiap kali ada pembelian ingredient baru (InventoryController::storePurchase):
```php
foreach ($ingredient->recipeItems as $recipeItem) {
    $recipeItem->recipe->menuItem?->syncHpp(); // Update menu HPP
}
```

---

#### D. Financial Balance Validation

**Daily P&L Validation**:
$$\text{Gross Profit}_{\text{daily}} = \sum \text{daily\_sale\_items.contribution\_margin}$$

**Monthly Reconciliation**:
$$\text{Net Profit}_{\text{monthly}} = \text{Gross Profit} - \text{Total Expenses}$$

Dapat di-export via [app/Http/Controllers/ReportController.php](app/Http/Controllers/ReportController.php):
```php
$totalRevenue = $sales->sum('total_revenue');
$totalHpp = $sales->sum('total_hpp');
$grossProfit = $sales->sum('gross_profit');
$totalExpenses = $expenses->sum('amount');
$netProfit = $grossProfit - $totalExpenses;
```

---

### IV.4.2 Validasi vs Benchmark Bisnis (Expert Judgment)

Hasil analisis BI dievaluasi terhadap **kriteria keputusan berbasis domain UMKM pangan**:

#### A. KPI Benchmarking untuk UMKM Kuliner

| KPI | Target Industri | Interpretasi |
|-----|-----------------|--------------|
| **Food Cost %** | 25-35% | Jika > 40%, cost structure bermasalah; jika < 20%, harga mungkin terlalu tinggi |
| **Gross Profit Margin** | 40-60% | Healthy range untuk retail F&B |
| **Inventory Turnover** | 15-30 hari | Optimal untuk perishable goods |
| **Menu Item Count** | 10-20 items | Too many = inventory complex; too few = limited offering |

**Evaluasi Dashboard** (DashboardController::index):
```php
// Food Cost % dievaluasi vs target 30%
$kpi['fc_percent'] = round(($totalHpp / $totalRev) * 100, 2);
// Jika fc_percent > 35%, flag warning untuk UMKM owner
```

#### B. BCG Classification Validation

Hasil klasifikasi divalidasi melalui **business logic reasonableness**:

1. **Star Items**: Perlu 2-3 items sebagai core revenue driver
   - Jika tidak ada Star → menu structure perlu review
   - Jika semua Star → data masih immature atau pricing perlu penyesuaian

2. **Plowhorse Items**: Diharapkan 3-5 items sebagai volume anchor
   - Terlalu banyak Plowhorse → low differentiation
   - Terlalu sedikit → profit margin terancam

3. **Puzzle Items**: High-margin niche products (expected 1-3 items)
   - Perlu aggressive marketing untuk convert ke Star
   - Jika bertahan > 3 bulan → evaluasi market fit

4. **Dog Items**: Dead stock yang perlu dieliminasi
   - Action item: hapus dari menu atau rebrand
   - Track opname waste untuk Dog items

#### C. Prediksi Penjualan Accuracy Evaluation

Linear regression predictions dievaluasi dengan **rolling accuracy**:

1. **In-Sample Fit**: Model ditrain pada hari 1 - N, diprediksi hari N+1, dibanding actual hari N+1
2. **Out-of-Sample**: Setelah akumulasi 30+ hari, akurasi dihitung dengan MAPE (Mean Absolute Percentage Error)

$$\text{MAPE} = \frac{1}{m} \sum_{i=1}^{m} \left| \frac{\hat{y}_i - y_i}{y_i} \right| \times 100\%$$

**Guideline**:
- **MAPE < 10%**: Excellent (dapat digunakan untuk inventory planning)
- **MAPE 10-20%**: Good (gunakan dengan caution factor 1.2)
- **MAPE > 20%**: Fair (primarily untuk trend indication, bukan quantity planning)

#### D. Data Quality Assessment

Confidence Score digunakan sebagai filter untuk presentation hasil analisis:

```php
if ($overall >= 70) {
    $presentation = "RECOMMENDED"; // Display dengan confidence
} elseif ($overall >= 40) {
    $presentation = "INDICATIVE ONLY"; // Display dengan caveat
} else {
    $presentation = "LEARNING MODE"; // Display dengan disclaimer
}
```

---

### IV.4.3 Audit Trail & Reproducibility

Sistem mencatat metadata untuk setiap analisis guna memastikan reproducibility:

#### A. Data Snapshot
Setiap periode analisis (e.g., SPK Report) mencatat:
- `startDate`, `endDate` (filter period)
- `totalQtyAll`, `averageCM`, `averageMM` (benchmark metrics)
- Generated timestamp

#### B. Source Data Attribution
Setiap KPI mereferensi source table dan kolom:
- Revenue ← daily_sales.total_revenue (SUM dari daily_sale_items.subtotal_revenue)
- HPP ← daily_sales.total_hpp (SUM dari daily_sale_items.subtotal_hpp)
- Margin ← daily_sale_items.contribution_margin

#### C. Model Version
Linear Regression model mencatat:
- Jumlah data point (n)
- Slope (b) dan intercept (a)
- Confidence interval

---

### IV.4.4 Komunikasi Hasil ke Stakeholder (UMKM Owner)

Hasil analisis dikomunikasikan melalui beberapa format:

#### A. Dashboard Interaktif
[DashboardController::index](app/Http/Controllers/DashboardController.php) → view: `dashboard.blade.php`
- KPI cards (Revenue, Profit, FC%)
- Period selector (today, this_week, this_month)
- Real-time update (non-batch)

#### B. Strategic Report (SPK)
[SpkController::index](app/Http/Controllers/SpkController.php) → view: `spk.index`
- BCG Matrix visualization
- Per-menu recommendation
- Sortir berdasarkan priority (Star → Dog)

#### C. BI Analytics Hub
[BiHubController](app/Http/Controllers/BiHubController.php) → multiple views:
- Sales Trend (line chart, time series)
- Menu Ranking (bar chart, top 10 menus)
- Waste Analysis (stacked bar: sales vs waste)

#### D. PDF Export
[ReportController::exportFinance](app/Http/Controllers/ReportController.php)
- Financial P&L statement
- Inventory asset valuation
- Formal audit trail untuk compliance

---

## Kesimpulan (IV.5)

Sistem Aplikasi Produksi Naila mengimplementasikan **Business Intelligence Pipeline komprehensif** dengan paradigma CRISP-DM:

1. **Data Understanding**: 6 fact tables (daily_sales, daily_sale_items, purchases, expenses, production_logs, ingredients) + 4 primary dimensions (Time, Product, Channel, Organization)

2. **Data Preparation**: 
   - Extract: Real-time operational input via Controllers
   - Transform: Automated calculations (HPP, Margin, Moving Average) pada insertion
   - Load: Aggregated data di header tables (daily_sales, purchases) dan analytics views

3. **Data Modelling**:
   - BCG Classification: 4-cell matrix (Star, Plowhorse, Puzzle, Dog) dengan heuristik MM% & CM
   - Prediction: Linear Regression untuk 3-day forecast penjualan per menu

4. **Evaluation**:
   - Intra-system validation: daily balance, inventory flow, cost propagation
   - Business logic validation: KPI benchmarking vs industry standards
   - Confidence scoring: Data maturity assessment sebelum actionability

Sistem dirancang untuk UMKM dengan **low-code operational complexity** namun **high analytical capability**, memungkinkan data-driven decision making tanpa memerlukan dedicated data scientist.

