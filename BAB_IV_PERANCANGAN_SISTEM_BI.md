# BAB IV: PERANCANGAN SISTEM BUSINESS INTELLIGENCE DENGAN METODOLOGI CRISP-DM

## IV.1 Data Understanding (Pemahaman Data)

### IV.1.1 Identifikasi Sumber Data Primer

Sistem Aplikasi Produksi Naila mengintegrasikan data operasional dari lima modul utama yang membentuk keseluruhan ekosistem UMKM pangan. Setiap modul menghasilkan tabel terstruktur yang menjadi fondasi analisis data:

#### A. Modul Penjualan (Sales Module)
Tabel penjualan mencakup dua level agregasi:

**Level Header: `daily_sales` (Migration: 2026_05_01_063330_b_create_daily_sales_table.php)**
- **Atribut utama**: `id`, `user_id`, `sale_date`, `total_revenue`, `total_hpp`, `gross_profit`, `payment_method`, `notes`
- **Tipe data**: Decimal(12,2) untuk financial metrics, Date untuk temporal dimension
- **Kardinalitas**: Satu atau lebih record per hari (constraint unique `sale_date` dihapus di migration 2026_05_01_080422)
- **Referensi**: Foreign key ke tabel `users` (who, user_id), pencatatan transaksi level user per hari

**Level Detail: `daily_sale_items` (Migration: 2026_05_01_063331_a_create_daily_sale_items_table.php)**
- **Atribut utama**: `id`, `daily_sale_id`, `menu_item_id`, `qty_sold`, `selling_price`, `hpp_per_item`, `subtotal_revenue`, `subtotal_hpp`, `contribution_margin`
- **Fitur khusus**: Snapshot harga pada waktu transaksi (kolom `selling_price` dan `hpp_per_item` tidak diperbarui secara retroaktif)
- **Atribut derivatif**: `contribution_margin` dihitung pada saat insertion
- **Buyer segmentation**: Kolom `buyer_type` (Eceran, Reseller, Agen) untuk multi-channel analysis

Logika pengisian diatur dalam Model `DailySaleItem::makeFromQty()` — fungsi factory yang mengotomasi perhitungan subtotal berdasarkan formula:

$$CM = Q \times (P_{\text{jual}} - HPP_{\text{item}})$$

di mana $CM$ = contribution margin, $Q$ = kuantitas terjual, $P_{\text{jual}}$ = harga jual per item, dan $HPP_{\text{item}}$ = harga pokok produksi per item.

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
- **Computed attribute** (Model `RecipeItem::getCostAttribute()`):

$$C_{\text{item}} = q_i \times c_i$$

di mana $q_i$ = kuantitas bahan ke-$i$ dalam resep, dan $c_i$ = harga per unit bahan ke-$i$.

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
- **Purpose**: Dicatat setiap kali produksi dilakukan (melalui `InventoryController::storeProduction()`)
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

Atribut financial yang menjadi Key Performance Indicator (KPI) utama:

| KPI | Formula | Sumber | Interpretasi |
|-----|---------|--------|--------------|
| **Revenue** | `SUM(daily_sale_items.subtotal_revenue)` | daily sales → daily sale items | Total pendapatan penjualan |
| **HPP (COGS)** | `SUM(daily_sale_items.subtotal_hpp)` | daily sales → daily sale items | Total biaya produksi produk terjual |
| **Gross Profit** | Revenue − HPP | daily_sales.gross_profit | Laba kotor sebelum biaya operasional |
| **Contribution Margin** | `daily_sale_items.contribution_margin` per item | daily sale items | Kontribusi keuntungan per produk |
| **Food Cost %** | $(HPP / Revenue) \times 100\%$ | DashboardController formula | Persentase biaya bahan vs penjualan |
| **Pengeluaran Operasional** | `SUM(expenses.amount)` | expenses | Biaya non-COGS (gaji, listrik, dll) |
| **Net Profit** | Gross Profit − Pengeluaran Operasional | DashboardController formula | Laba bersih bisnis |

---

## IV.2 Data Preparation (ETL Implisit & Data Cleaning)

### IV.2.1 Proses Extract (Data Extraction)

Dalam sistem Aplikasi Produksi Naila, ekstraksi data dilakukan secara **real-time event-driven** tanpa batch processing eksplisit. Setiap transaksi langsung disimpan ke database melalui Controller-layer API.

#### A. Extract dari Penjualan (PosController::store)
**File**: `app/Http/Controllers/PosController.php`

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
    $menu = MenuItem::with('recipe.items.ingredient')->find($item['id']);
```

**Data Extraction Points**:
1. Per-item selling price diambil dari `MenuItem` berdasarkan `buyer_type` (`price_eceran` / `price_reseller` / `price_agen`)
2. HPP per-item diambil dari `MenuItem::$hpp` (snapshot saat transaksi)
3. Stok (raw & finished goods) di-extract dari `MenuItem::$current_stock` dan `Ingredient::$current_stock`

#### B. Extract dari Pembelian (InventoryController::storePurchase)
**File**: `app/Http/Controllers/InventoryController.php`

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
**File**: `app/Http/Controllers/DashboardController.php`

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

**Lokasi Logika**: `app/Models/Recipe.php` — method `calculateHpp()`

Rumus HPP per porsi:

$$HPP_{\text{porsi}} = \frac{\displaystyle\sum_{i=1}^{n} (q_i \times c_i)}{S} + P_k + O_h$$

di mana:
- $q_i$ = kuantitas bahan ke-$i$ dalam satu batch resep
- $c_i$ = harga per unit bahan ke-$i$ (`cost_per_unit`)
- $S$ = jumlah porsi per batch (`serving_qty`)
- $P_k$ = biaya kemasan per porsi (`packaging_cost`)
- $O_h$ = biaya overhead per porsi (`overhead_cost`)

**Implementasi**:
```php
// File: Recipe.php
public function calculateHpp(): float
{
    $totalBahan = 0;
    foreach ($this->items as $item) {
        $ingredient = $item->ingredient;
        $totalBahan += $item->quantity * $ingredient->cost_per_unit;
    }
    
    $hppPerPorsi = ($totalBahan / max($this->serving_qty, 1)) + 
                   $this->packaging_cost + 
                   $this->overhead_cost;
    return round($hppPerPorsi, 2);
}
```

**Trigger**: Dijalankan setiap kali ada perubahan ingredient cost (via `InventoryController::storePurchase()`).

**Data Quality Check**: Validasi $S > 0$ (denominator guard) untuk mencegah pembagian dengan nol.

---

#### B. Transform 2: Kalkulasi Contribution Margin Per Item Penjualan

**Lokasi Logika**: `app/Models/DailySaleItem.php` — method `makeFromQty()`

Rumus kalkulasi subtotal dan contribution margin:

$$R_{\text{sub}} = Q \times P_{\text{jual}}$$

$$HPP_{\text{sub}} = Q \times HPP_{\text{item}}$$

$$CM = R_{\text{sub}} - HPP_{\text{sub}}$$

di mana:
- $R_{\text{sub}}$ = subtotal pendapatan (`subtotal_revenue`)
- $Q$ = kuantitas terjual (`qty_sold`)
- $P_{\text{jual}}$ = harga jual sesuai segmen pembeli (`selling_price`)
- $HPP_{\text{sub}}$ = subtotal harga pokok (`subtotal_hpp`)
- $HPP_{\text{item}}$ = HPP per item snapshot saat transaksi (`hpp_per_item`)
- $CM$ = contribution margin (`contribution_margin`)

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
        'qty_sold'            => $qty,
        'selling_price'       => $sellingPrice,
        'hpp_per_item'        => $menu->hpp,
        'subtotal_revenue'    => $subtotalRevenue,
        'subtotal_hpp'        => $subtotalHpp,
        'contribution_margin' => $subtotalRevenue - $subtotalHpp,
    ]);
}
```

**Data Quality**: Nilai negatif pada selling price atau HPP tertangkap di validation layer (request validation di PosController).

---

#### C. Transform 3: Agregasi Daily Sales Header

**Lokasi Logika**: `app/Models/DailySale.php` — method `recalculateTotals()`

Proses agregasi dari detail items ke header:

$$R_{\text{total}} = \sum_{i} R_{\text{sub},i}$$

$$HPP_{\text{total}} = \sum_{i} HPP_{\text{sub},i}$$

$$GP = R_{\text{total}} - HPP_{\text{total}}$$

di mana $GP$ = gross profit, dan indeks $i$ merentang seluruh item dalam satu record `daily_sale`.

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

**Consistency Check**: Jumlah dari items harus sama dengan header totals (divalidasi pasca-transaksi).

---

#### D. Transform 4: Moving Average Cost of Ingredient

**Lokasi Logika**: `app/Http/Controllers/InventoryController.php` — method `storePurchase()`

Metode **Weighted Average** digunakan untuk memperbarui harga pokok bahan:

$$TV_{\text{lama}} = S_{\text{lama}} \times C_{\text{lama}}$$

$$TV_{\text{baru}} = TV_{\text{lama}} + P_{\text{beli}}$$

$$S_{\text{baru}} = S_{\text{lama}} + Q_{\text{beli}}$$

$$C_{\text{baru}} = \frac{TV_{\text{baru}}}{S_{\text{baru}}}$$

di mana:
- $TV$ = total nilai inventori (*total value*)
- $S$ = stok saat ini (`current_stock`)
- $C$ = harga pokok per unit (`cost_per_unit`)
- $P_{\text{beli}}$ = total harga pembelian (`total_price`)
- $Q_{\text{beli}}$ = kuantitas yang dibeli (`quantity`)

**Implementasi**:
```php
// InventoryController.php storePurchase()
$oldTotalValue = $ingredient->current_stock * $ingredient->cost_per_unit;
$newTotalValue = $oldTotalValue + $request->total_price;
$newTotalStock = $ingredient->current_stock + $request->quantity;

$newAvgPrice = $newTotalStock > 0
    ? ($newTotalValue / $newTotalStock)
    : $ingredient->cost_per_unit;

$ingredient->current_stock = $newTotalStock;
$ingredient->cost_per_unit = $newAvgPrice;
$ingredient->save();
```

**Cascade Update**: Setelah ingredient cost diperbarui, semua menu yang menggunakan ingredient tersebut perlu direcalculate HPP-nya:
```php
foreach ($ingredient->recipeItems as $recipeItem) {
    $recipeItem->recipe->menuItem?->syncHpp();
}
```

---

#### E. Transform 5: Kalkulasi Food Cost Percentage

**Lokasi Logika**: `app/Http/Controllers/DashboardController.php`

$$FC\% = \frac{HPP_{\text{total}}}{R_{\text{total}}} \times 100\%$$

di mana $FC\%$ = food cost percentage, $HPP_{\text{total}}$ = total harga pokok penjualan, dan $R_{\text{total}}$ = total pendapatan.

**Implementasi**:
```php
$kpi = [
    'revenue'    => $totalRev,
    'profit'     => $totalProfit,
    'fc_percent' => $totalRev > 0 ? round(($totalHpp / $totalRev) * 100, 2) : 0,
];
```

**Safeguard**: Pengecekan pembagian dengan nol — jika $R_{\text{total}} = 0$, maka $FC\% = 0$.

---

### IV.2.3 Data Cleaning & Validasi Integritas

Data cleaning dilakukan pada dua tahap: **input validation** dan **operational validation**.

#### A. Input Validation (Request Level)

Setiap endpoint Controller mengimplementasikan `$request->validate()` sebagai lapisan pertahanan antara input pengguna dan database:

**PosController::store()**
```php
$request->validate([
    'buyer_type'     => 'required|string',
    'payment_method' => 'required|string',
    'cart'           => 'required|string',
]);

$cart = json_decode($request->cart, true);
if (empty($cart)) {
    return redirect()->back()->with('error', 'Keranjang kosong!');
}
```

**InventoryController::storePurchase()**
```php
$request->validate([
    'ingredient_id' => 'required|exists:ingredients,id',
    'supplier_id'   => 'nullable|exists:suppliers,id',
    'quantity'      => 'required|numeric|min:0.1',
    'total_price'   => 'required|numeric|min:0',
]);
```

**InventoryController::storeProduction()**
```php
$request->validate([
    'menu_id'  => 'required|exists:menu_items,id',
    'quantity' => 'required|integer|min:1',
]);
```

#### B. Business Logic Validation

Selain input validation, terdapat validasi bisnis untuk memastikan transaksi logis:

**Stock Sufficiency Check** (`InventoryController::storeProduction`):
```php
if ($menu->production_capacity < $qty) {
    return redirect()->back()
        ->with('error', 'Bahan mentah tidak cukup untuk memproduksi sejumlah ini!');
}
```

Atribut `production_capacity` dihitung dari stok ingredient yang tersedia dibandingkan kebutuhan resep.

**Cost Consistency Check** (`InventoryController::storeOpname`):
```php
$difference = $request->actual_stock - $ingredient->current_stock;

if ($difference < 0) {
    $lossValue = abs($difference) * $ingredient->cost_per_unit;
    Expense::create([...]);
}
```

Sistem mencatat discrepancy stok sebagai expense (waste tracking) — fitur data quality yang penting untuk BI.

---

#### C. Temporal Consistency (Date Validation)

Semua tanggal disimpan dalam format `DATE` dan ditetapkan dari sisi server (implicit dalam business logic):
```php
'purchase_date'   => now()->format('Y-m-d'),
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

Cascade delete policy memastikan tidak ada orphaned records yang tersisa.

---

## IV.3 Data Modelling (Pemodelan Data untuk Analisis)

### IV.3.1 Dimensional Model: Fact Table & Dimension Tables

Meskipun aplikasi menggunakan **operational OLTP schema**, sistem BI mengakses data melalui dimensional model implisit:

#### Fact Tables

**Fact Table: `daily_sale_items`** (granularity: per line item transaksi penjualan)

*Catatan: Setiap transaksi penjualan dapat memiliki multiple records `daily_sale_items` — satu record per produk yang terjual. Tidak ada guarantee unique per hari per channel, karena dalam satu hari bisa ada multiple transaksi untuk produk yang sama dengan channel yang sama.*

| Dimensi | Tabel Referensi | Atribut |
|---------|-----------------|---------|
| Time | Implicit | `sale_date` (dari `daily_sales`) |
| Product | menu_items | `menu_item_id`, `name`, `category`, `recipe_id` |
| Channel | Implicit | `buyer_type` (Eceran/Reseller/Agen) |
| Organization | users | `user_id` (kasir yang menginput) |
| Transaction | daily_sales | `daily_sale_id` (FK ke transaksi penjualan) |

**Measures** (numeric facts):
- `qty_sold` (kuantitas dalam satu line item transaksi)
- `subtotal_revenue` (pendapatan dari line item ini)
- `subtotal_hpp` (COGS dari line item ini)
- `contribution_margin` (laba di level line-item)

**Fact Table: `production_logs`** (granularity: per produk per hari produksi)

| Atribut | Tipe | Purpose |
|---------|------|---------|
| `menu_item_id` | FK | Product dimension |
| `quantity` | INT | Volume produksi |
| `production_date` | DATE | Time dimension |

---

#### Dimension Tables

**Dimensi: Time** (Calendar hierarchy)
- Source: `daily_sales.sale_date`, `purchases.purchase_date`, `production_logs.production_date`
- Granularity: Daily
- Hierarchy: Day → Week → Month → Year

**Dimensi: Product (Menu)**
- Tabel: `menu_items`, `recipes`, `recipe_items`
- Atribut: `name`, `category`, `hpp`, `price_eceran`, `price_reseller`, `price_agen`
- Hierarchy: Category → Menu Item → Recipe

**Dimensi: Channel**
- Source: `daily_sale_items.buyer_type`
- Values: {Eceran, Reseller, Agen}

**Dimensi: Ingredient** (untuk Supply Chain Analysis)
- Tabel: `ingredients`
- Atribut: `name`, `unit`, `supplier_id`, `cost_per_unit`, `current_stock`
- Support: Waste analysis, procurement optimization

---

### IV.3.2 Klasifikasi BCG Matrix: Implementasi Decision Tree

**File Referensi**: `app/Http/Controllers/AnalysisController.php` — method `runClassification()` dan `app/Http/Controllers/SpkController.php` — method `index()`

#### A. Metrik Klasifikasi

Sistem menggunakan dua dimensi BCG untuk klasifikasi menu:

**Dimensi 1: Menu Mix (Popularitas/Market Share) — $MM\%$**

$$MM\%_j = \frac{Q_j}{Q_{\text{total}}} \times 100\%$$

di mana $Q_j$ = kuantitas terjual menu ke-$j$, dan $Q_{\text{total}}$ = total kuantitas seluruh menu.

Threshold (simple average dari semua menu):

$$\overline{MM} = \frac{100\%}{N}$$

di mana $N$ = jumlah menu item yang terjual ($Q_j > 0$).

Interpretasi:
- $MM\%_j \geq \overline{MM}$: *High Market Share* ("Market Leader")
- $MM\%_j < \overline{MM}$: *Low Market Share* ("Niche Product")

**Dimensi 2: Contribution Margin (Profitabilitas) — $CM$**

$$CM_{\text{per item},j} = \frac{CM_{j}}{Q_j}$$

$$\overline{CM} = \frac{CM_{\text{total}}}{Q_{\text{total}}}$$

di mana $CM_j$ = total contribution margin menu ke-$j$, dan $CM_{\text{total}}$ = total contribution margin semua menu.

Interpretasi:
- $CM_{\text{per item},j} \geq \overline{CM}$: *High Profit* ("High Margin")
- $CM_{\text{per item},j} < \overline{CM}$: *Low Profit* ("Low Margin")

---

#### B. Matriks Klasifikasi

Kombinasi kedua dimensi menghasilkan empat kategori BCG:

| $MM\%$ vs $CM$ | $CM \geq \overline{CM}$ | $CM < \overline{CM}$ |
|----------------|-------------------------|----------------------|
| $MM\% \geq \overline{MM}$ | **STAR** 🌟 | **PLOWHORSE** 🐴 |
| $MM\% < \overline{MM}$ | **PUZZLE** 🧩 | **DOG** 🐕 |

**Kode Implementasi** (`SpkController::index`):
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

| Kategori | $MM\%$ | $CM$ | Karakteristik | Rekomendasi Strategis |
|----------|--------|------|---------------|-----------------------|
| **Star** | ↑ | ↑ | Best seller dengan margin tinggi | Pertahankan kualitas; jadikan signature; tingkatkan promosi |
| **Plowhorse** | ↑ | ↓ | High volume, low margin | Naikkan harga bertahap atau kurangi porsi; optimasi cost |
| **Puzzle** | ↓ | ↑ | Low volume, high margin | Gencar promosi; bundling; discount strategis untuk acquire volume |
| **Dog** | ↓ | ↓ | Slow mover, low profit | Evaluasi resep/cost; hapus jika tidak prospek; rebrand |

---

### IV.3.3 Prediksi Penjualan: Linear Regression Model

**File Referensi**: `app/Http/Controllers/AnalysisController.php` — method `runRegression()`

#### A. Model Matematika

Sistem mengimplementasikan **Simple Linear Regression** dengan formula:

$$\hat{y} = a + bx$$

di mana:
- $x$ = urutan hari ke-$x$ dalam periode observasi ($x = 1, 2, 3, \ldots, n$)
- $y$ = kuantitas terjual pada hari ke-$x$
- $a$ = intercept (baseline volume)
- $b$ = slope (tren per hari)

#### B. Parameter Estimasi (Least Squares Method)

$$b = \frac{n \displaystyle\sum_{i=1}^{n} x_i y_i - \left(\sum_{i=1}^{n} x_i\right)\!\left(\sum_{i=1}^{n} y_i\right)}{n \displaystyle\sum_{i=1}^{n} x_i^2 - \left(\sum_{i=1}^{n} x_i\right)^2}$$

$$a = \bar{y} - b\bar{x}$$

di mana $\bar{x}$ dan $\bar{y}$ adalah rata-rata dari masing-masing variabel.

**Implementasi Kode**:
```php
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
```

#### C. Prediksi (Forecast)

Untuk $k$ hari ke depan setelah hari terakhir observasi $n$:

$$\hat{y}_{n+k} = a + b(n + k), \quad k = 1, 2, 3$$

Dengan validasi $\hat{y}_{n+k} \geq 0$ untuk mencegah prediksi bernilai negatif:

```php
$nextDayPredictions = [];
for ($day = 1; $day <= 3; $day++) {
    $predictedX = $n + $day;
    $predictedY = max(0, round($a + ($b * $predictedX)));
    $nextDayPredictions[] = [
        'day_label'      => ['Besok', 'Lusa', 'Besoknya lagi'][$day-1],
        'predicted_qty'  => $predictedY,
    ];
}
```

**Interpretasi Slope**:
- $b > 0$: Tren naik (penjualan meningkat)
- $b < 0$: Tren turun (penjualan menurun)
- $b \approx 0$: Tren stabil/flat

#### D. Reliability Indicator (Data Confidence Score)

**File**: `AnalysisController::calculateDataScore()`

Sistem mengevaluasi kualitas data sebelum memberikan prediksi:

$$\text{Score} = \frac{S_d + S_r + S_m}{3}$$

di mana masing-masing komponen dihitung sebagai berikut:

$$S_d = \min\!\left(\frac{d}{30},\, 1\right) \times 100$$

$$S_r = \min\!\left(\frac{r}{100},\, 1\right) \times 100$$

$$S_m = \min\!\left(\frac{m}{3},\, 1\right) \times 100$$

dengan $d$ = jumlah hari berbeda yang tercatat (target: 30 hari), $r$ = total jumlah transaksi (target: 100 transaksi), dan $m$ = jumlah menu aktif (target: 3 produk).

**Klasifikasi Confidence**:
- $\text{Score} \geq 70$: "Cukup Baik" — prediksi dapat dipercaya
- $40 \leq \text{Score} < 70$: "Sedang" — prediksi perlu cross-validation dengan expert judgment
- $\text{Score} < 40$: "Masih Sedikit" — model masih dalam *learning phase*, akurasi tidak terjamin

**Implementasi**:
```php
private function calculateDataScore(): array
{
    $days    = DailySale::select('sale_date')->distinct()->count();
    $records = DailySaleItem::count();
    $menus   = MenuItem::count();

    $dayScore    = min(($days / 30) * 100, 100);
    $recordScore = min(($records / 100) * 100, 100);
    $menuScore   = min(($menus / 3) * 100, 100);

    $overall = round(($dayScore + $recordScore + $menuScore) / 3);

    if ($overall >= 70) {
        $level   = 'good';
        $message = 'Sistem sudah memiliki cukup data untuk mulai mengenali pola penjualan Anda.';
    } elseif ($overall >= 40) {
        $level   = 'moderate';
        $message = 'Tebakan AI masih bisa meleset. Terus gunakan aplikasi untuk memperbanyak data.';
    } else {
        $level   = 'low';
        $message = 'Akurasinya belum bisa dijadikan patokan utama. Tebakan akan semakin akurat setelah data terkumpul.';
    }

    return compact('overall', 'level', 'message');
}
```

---

### IV.3.4 Early Warning System: Decision Tree untuk Prediksi Trend

**File Referensi**: `app/Http/Controllers/AnalysisController.php` — method `runClassification()`

#### A. Trend Calculation

Sistem menggunakan **simple trend detection** dengan membagi data penjualan menjadi dua periode:

$$Q_{\text{awal}} = \sum_{i=1}^{\lfloor n/2 \rfloor} q_i$$

$$Q_{\text{akhir}} = \sum_{i=\lfloor n/2 \rfloor + 1}^{n} q_i$$

di mana $q_i$ = kuantitas terjual pada hari ke-$i$, dan $n$ = total jumlah hari observasi.

**Trend Rules**:
- **Naik**: Jika $Q_{\text{akhir}} > 1{,}1 \times Q_{\text{awal}}$ (peningkatan $> 10\%$)
- **Turun**: Jika $Q_{\text{akhir}} < 0{,}9 \times Q_{\text{awal}}$ (penurunan $> 10\%$)
- **Stabil**: Selain kedua kondisi di atas

**Implementasi**:
```php
$halfCount = floor($sales->count() / 2);
if ($halfCount > 0) {
    $firstHalfQty  = $sales->take($halfCount)->sum('qty_sold');
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
$confidence       = 0;

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
    'confidence'        => $confidence,
];
```

---

## IV.4 Evaluation (Evaluasi & Validasi Hasil Analisis)

### IV.4.1 Validasi Intra-System (Consistency Checks)

Sistem melakukan cross-validation antara berbagai komponen untuk memastikan data integrity dan logika bisnis konsisten.

#### A. Daily Sales Balance Validation

Setiap record transaksi penjualan (`daily_sales` header) harus memenuhi **zero-sum balance** antara semua line items (`daily_sale_items`) dan totals di header:

$$R_{\text{total}} = \sum_{i} R_{\text{sub},i}$$

$$HPP_{\text{total}} = \sum_{i} HPP_{\text{sub},i}$$

$$GP = R_{\text{total}} - HPP_{\text{total}}$$

di mana indeks $i$ merentang seluruh `daily_sale_items` dalam satu record `daily_sales`.

**Implementasi** (Post-transaction):
```php
// PosController::store()
$sale->recalculateTotals();
```

Jika terdapat discrepancy, hal tersebut mengindikasikan bug dalam transaction processing.

#### B. Inventory Flow Validation

**Bahan Baku (Raw Materials)**:

$$S_{\text{bahan, akhir}} = S_{\text{awal}} + Q_{\text{beli}} - U_{\text{produksi}} - W_{\text{waste}}$$

**Produk Jadi (Finished Goods)**:

$$S_{\text{menu, akhir}} = S_{\text{awal}} + P_{\text{produksi}} - V_{\text{jual}}$$

di mana:
- $S$ = stok (`current_stock`)
- $Q_{\text{beli}}$ = kuantitas pembelian bahan
- $U_{\text{produksi}}$ = penggunaan bahan dalam produksi
- $W_{\text{waste}}$ = kerugian bahan (dicatat via opname)
- $P_{\text{produksi}}$ = kuantitas produksi menu
- $V_{\text{jual}}$ = kuantitas penjualan menu

**Logika di Code**:
- Setiap pembelian ingredient → `current_stock` bertambah (`InventoryController::storePurchase`)
- Setiap produksi → ingredient berkurang, MenuItem bertambah (`InventoryController::storeProduction`)
- Setiap penjualan → MenuItem berkurang (`PosController::store`)
- Setiap opname → adjust dengan mencatat waste sebagai Expense

#### C. Cost Consistency Validation

**HPP Propagation Check**: Setiap `MenuItem.hpp` harus selalu konsisten dengan hasil `Recipe::calculateHpp()`.

Trigger — setiap kali ada pembelian ingredient baru (`InventoryController::storePurchase`):
```php
foreach ($ingredient->recipeItems as $recipeItem) {
    $recipeItem->recipe->menuItem?->syncHpp();
}
```

---

#### D. Financial Balance Validation

**Daily P&L Validation**:

$$GP_{\text{harian}} = \sum_{i} CM_i$$

**Monthly Reconciliation**:

$$NP_{\text{bulanan}} = GP_{\text{bulanan}} - E_{\text{total}}$$

di mana $NP$ = net profit dan $E_{\text{total}}$ = total pengeluaran operasional.

Dapat di-export via `app/Http/Controllers/ReportController.php`:
```php
$totalRevenue  = $sales->sum('total_revenue');
$totalHpp      = $sales->sum('total_hpp');
$grossProfit   = $sales->sum('gross_profit');
$totalExpenses = $expenses->sum('amount');
$netProfit     = $grossProfit - $totalExpenses;
```

---

### IV.4.2 Validasi vs Benchmark Bisnis (Expert Judgment)

Hasil analisis BI dievaluasi terhadap **kriteria keputusan berbasis domain UMKM pangan**:

#### A. KPI Benchmarking untuk UMKM Kuliner

| KPI | Target Industri | Interpretasi |
|-----|-----------------|--------------|
| **Food Cost %** | 25–35% | Jika $> 40\%$: cost structure bermasalah; jika $< 20\%$: harga mungkin terlalu tinggi |
| **Gross Profit Margin** | 40–60% | Rentang sehat untuk retail F&B |
| **Inventory Turnover** | 15–30 hari | Optimal untuk perishable goods |
| **Menu Item Count** | 10–20 items | Terlalu banyak = inventory kompleks; terlalu sedikit = pilihan terbatas |

**Evaluasi Dashboard** (`DashboardController::index`):
```php
$kpi['fc_percent'] = round(($totalHpp / $totalRev) * 100, 2);
// Jika fc_percent > 35%, flag warning untuk UMKM owner
```

#### B. BCG Classification Validation

Hasil klasifikasi divalidasi melalui **business logic reasonableness**:

1. **Star Items**: Diharapkan 2–3 item sebagai core revenue driver.
   - Tidak ada Star → struktur menu perlu review.
   - Semua Star → data belum matang atau pricing perlu penyesuaian.

2. **Plowhorse Items**: Diharapkan 3–5 item sebagai volume anchor.
   - Terlalu banyak Plowhorse → diferensiasi rendah.
   - Terlalu sedikit → profit margin terancam.

3. **Puzzle Items**: High-margin niche products (diharapkan 1–3 item).
   - Perlu aggressive marketing untuk convert ke Star.
   - Jika bertahan $> 3$ bulan → evaluasi market fit.

4. **Dog Items**: Dead stock yang perlu dieliminasi.
   - Action item: hapus dari menu atau rebrand.
   - Track opname waste untuk Dog items.

#### C. Prediksi Penjualan: Accuracy Evaluation

Prediksi linear regression dievaluasi dengan **Mean Absolute Percentage Error (MAPE)**:

$$MAPE = \frac{1}{m} \sum_{i=1}^{m} \left| \frac{\hat{y}_i - y_i}{y_i} \right| \times 100\%$$

di mana $m$ = jumlah hari evaluasi, $\hat{y}_i$ = nilai prediksi, dan $y_i$ = nilai aktual.

**Guideline**:
- $MAPE < 10\%$: Excellent — dapat digunakan untuk inventory planning
- $10\% \leq MAPE \leq 20\%$: Good — gunakan dengan caution factor 1,2
- $MAPE > 20\%$: Fair — terutama untuk indikasi tren, bukan quantity planning

#### D. Data Quality Assessment

Confidence Score digunakan sebagai filter untuk presentasi hasil analisis:

```php
if ($overall >= 70) {
    $presentation = "RECOMMENDED";     // Tampilkan dengan confidence
} elseif ($overall >= 40) {
    $presentation = "INDICATIVE ONLY"; // Tampilkan dengan caveat
} else {
    $presentation = "LEARNING MODE";   // Tampilkan dengan disclaimer
}
```

---

### IV.4.3 Audit Trail & Reproducibility

Sistem mencatat metadata untuk setiap analisis guna memastikan reproducibility:

#### A. Data Snapshot
Setiap periode analisis (contoh: SPK Report) mencatat:
- `startDate`, `endDate` (filter period)
- $Q_{\text{total}}$, $\overline{CM}$, $\overline{MM}$ (benchmark metrics)
- Generated timestamp

#### B. Source Data Attribution
Setiap KPI mereferensi source table dan kolom:
- Revenue ← `daily_sales.total_revenue` (SUM dari `daily_sale_items.subtotal_revenue`)
- HPP ← `daily_sales.total_hpp` (SUM dari `daily_sale_items.subtotal_hpp`)
- Margin ← `daily_sale_items.contribution_margin`

#### C. Model Version
Linear Regression model mencatat:
- Jumlah data point ($n$)
- Slope ($b$) dan intercept ($a$)
- Confidence Score ($\text{Score}$)

---

### IV.4.4 Komunikasi Hasil ke Stakeholder (UMKM Owner)

Hasil analisis dikomunikasikan melalui beberapa format:

#### A. Dashboard Interaktif
`DashboardController::index` → view: `dashboard.blade.php`
- KPI cards (Revenue, Profit, $FC\%$)
- Period selector (today, this_week, this_month)
- Real-time update (non-batch)

#### B. Strategic Report (SPK)
`SpkController::index` → view: `spk.index`
- BCG Matrix visualization
- Per-menu recommendation
- Diurutkan berdasarkan priority (Star → Dog)

#### C. BI Analytics Hub
`BiHubController` → multiple views:
- Sales Trend (line chart, time series)
- Menu Ranking (bar chart, top 10 menus)
- Waste Analysis (stacked bar: sales vs waste)

#### D. PDF Export
`ReportController::exportFinance`
- Financial P&L statement
- Inventory asset valuation
- Formal audit trail untuk compliance

---

## IV.5 Kesimpulan

Sistem Aplikasi Produksi Naila mengimplementasikan **Business Intelligence Pipeline komprehensif** dengan paradigma CRISP-DM:

1. **Data Understanding**: 6 fact tables (`daily_sales`, `daily_sale_items`, `purchases`, `expenses`, `production_logs`, `ingredients`) dengan 4 primary dimensions (Time, Product, Channel, Organization).

2. **Data Preparation**:
   - *Extract*: Real-time operational input via Controllers
   - *Transform*: Automated calculations (HPP, Margin, Moving Average) pada insertion
   - *Load*: Aggregated data di header tables (`daily_sales`, `purchases`) dan analytics views

3. **Data Modelling**:
   - BCG Classification: 4-cell matrix (Star, Plowhorse, Puzzle, Dog) dengan heuristik $MM\%$ dan $CM$
   - Prediction: Linear Regression untuk 3-day forecast penjualan per menu

4. **Evaluation**:
   - Intra-system validation: daily balance, inventory flow, cost propagation
   - Business logic validation: KPI benchmarking vs industry standards
   - Confidence scoring: Data maturity assessment sebelum actionability

Sistem dirancang untuk UMKM dengan **low-code operational complexity** namun **high analytical capability**, memungkinkan pengambilan keputusan berbasis data tanpa memerlukan dedicated data scientist.
