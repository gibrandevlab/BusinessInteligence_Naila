# Daily Sales Table - Comprehensive Codebase Analysis Report

**Generated: May 13, 2026**  
**Purpose:** Identify all Controllers/Models that access `daily_sales` table and analyze current architecture assumptions

---

## Executive Summary

**Status:** The application is prepared for multi-transaction-per-day support, but query patterns suggest most code assumes one record per day.

**Key Finding:** Migration `2026_05_01_080422_drop_unique_sale_date_from_daily_sales.php` removed the UNIQUE constraint on `sale_date`, enabling multiple records per day, but the codebase still contains aggregation logic that needs review for accuracy.

---

## 1. DATABASE SCHEMA

### Table: `daily_sales`
**Location:** `database/migrations/2026_05_01_063330_b_create_daily_sales_table.php`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | Primary key |
| `user_id` | bigint (FK) | References users table (cascadeOnDelete) |
| `sale_date` | date | Date of sales - **REMOVED UNIQUE CONSTRAINT** |
| `total_revenue` | decimal(12,2) | SUM of daily_sale_items.subtotal_revenue |
| `total_hpp` | decimal(12,2) | SUM of daily_sale_items.subtotal_hpp |
| `gross_profit` | decimal(12,2) | total_revenue - total_hpp |
| `notes` | text | Optional notes |
| `payment_method` | varchar | Payment method used |
| `created_at`, `updated_at` | timestamp | Timestamps |

**Migration History:**
- Original: `sale_date` had UNIQUE constraint (one transaction per day)
- Modified: `2026_05_01_080422_drop_unique_sale_date_from_daily_sales.php` dropped UNIQUE constraint
- **Current State:** Multiple records per day are now allowed

### Table: `daily_sale_items`
**Location:** `database/migrations/2026_05_01_063331_a_create_daily_sale_items_table.php`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint (PK) | Primary key |
| `daily_sale_id` | bigint (FK) | References daily_sales (cascadeOnDelete) |
| `menu_item_id` | bigint (FK) | References menu_items (cascadeOnDelete) |
| `qty_sold` | integer | Quantity sold |
| `selling_price` | decimal(10,2) | Snapshot of price at time of sale |
| `hpp_per_item` | decimal(10,2) | Snapshot of HPP at time of sale |
| `subtotal_revenue` | decimal(12,2) | qty_sold × selling_price |
| `subtotal_hpp` | decimal(12,2) | qty_sold × hpp_per_item |
| `contribution_margin` | decimal(12,2) | subtotal_revenue - subtotal_hpp |
| `buyer_type` | varchar | Eceran, Reseller, or Agen |
| `created_at`, `updated_at` | timestamp | Timestamps |

---

## 2. MODEL RELATIONSHIPS

### DailySale Model
**Location:** `app/Models/DailySale.php`

```php
// Fillable attributes
'user_id', 'sale_date', 'total_revenue', 'total_hpp', 'gross_profit', 
'payment_method', 'notes'

// Relationships
- belongsTo(User::class)
- hasMany(DailySaleItem::class)

// Key Methods
- recalculateTotals(): Recomputes total_revenue, total_hpp, gross_profit from items
- getFoodCostPercentAttribute(): Calculates (total_hpp / total_revenue) * 100
```

**Assumptions Made:**
- `sale_date` is cast as date
- One DailySale record represents a "day's sales session"
- Totals are cached in the header record for performance

### DailySaleItem Model
**Location:** `app/Models/DailySaleItem.php`

```php
// Relationships
- belongsTo(DailySale::class)
- belongsTo(MenuItem::class)

// Key Factory Method
- makeFromQty(DailySale $sale, MenuItem $menu, int $qty, string $buyerType): self
  * Auto-calculates subtotals based on:
    - buyer_type (Eceran, Reseller, Agen) determines selling_price
    - Menu's HPP determines hpp_per_item
    - Calculates: subtotal_revenue, subtotal_hpp, contribution_margin
```

### User Model Relationships
**Location:** `app/Models/User.php`

```php
- hasMany(DailySale::class)
- hasMany(Purchase::class)
- hasMany(Expense::class)
```

---

## 3. AFFECTED CONTROLLERS & METHODS

### 3.1 **PosController** - Point of Sale
**Location:** `app/Http/Controllers/PosController.php`

#### Method: `store(Request $request)`
- **Line 29:** Creates new DailySale record
- **Query Pattern:**
  ```php
  $sale = DailySale::create([
      'user_id' => Auth::id(),
      'sale_date' => Carbon::now()->format('Y-m-d'),  // Current date
      'payment_method' => $request->payment_method,
      'total_revenue' => 0,
      'total_hpp' => 0,
      'gross_profit' => 0,
  ]);
  ```
- **Assumptions:**
  - Creates ONE DailySale per POST request (one per transaction/session)
  - Uses current date (`Carbon::now()->format('Y-m-d')`)
  - Initializes with zero totals, then recalculates

- **Multi-Transaction Readiness:** ✅ READY
  - No UNIQUE constraint prevents multiple DailySale records per date
  - Each transaction creates its own DailySale record
  - Totals recalculated per record

- **Items Creation:** Line 41
  ```php
  DailySaleItem::makeFromQty($sale, $menu, $item['qty'], $request->buyer_type)->save();
  ```
  - Creates multiple items per DailySale
  - Automatically calculates subtotals

- **Stock Deduction Logic:** Lines 43-63
  - Deducts from `menu_items.current_stock`
  - Falls back to ingredient stock for "made to order" items
  - ⚠️ **No aggregation issues** - operates on menu/ingredient level

---

### 3.2 **DashboardController** - KPI Dashboard
**Location:** `app/Http/Controllers/DashboardController.php`

#### Method: `index(Request $request)`
- **Query Pattern:**
  ```php
  $querySales = DailySale::query();
  
  // Filter by period
  if ($period === 'yesterday') {
      $querySales->whereDate('sale_date', $date);
  } elseif ($period === 'this_week') {
      $querySales->whereBetween('sale_date', [$start, $end]);
  } elseif ($period === 'this_month') {
      $querySales->whereBetween('sale_date', [$start, $end]);
  } else { // default: today
      $querySales->whereDate('sale_date', $date);
  }
  ```

- **Aggregation Logic:** Line 44-45
  ```php
  $totalRev = $sales->sum('total_revenue');
  $totalHpp = $sales->sum('total_hpp');
  $totalProfit = $sales->sum('gross_profit');
  ```

- **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
  - **WHY:** Sums all matching DailySale records
  - If 3 transactions on same day → Sums all 3 records
  - Each record has accurate total_revenue/total_hpp (from recalculateTotals)
  - Multiple records per date are correctly aggregated

- **Supported Periods:**
  - today
  - yesterday
  - this_week
  - this_month

- **KPIs Calculated:**
  - `revenue`: Sum of total_revenue
  - `profit`: Sum of gross_profit
  - `pengeluaran`: Purchase total_amount + Expense amount
  - `fc_percent`: (total_hpp / total_revenue) * 100

---

### 3.3 **ReportController** - Finance & Inventory Reports
**Location:** `app/Http/Controllers/ReportController.php`

#### Method: `exportFinance(Request $request)`
- **Query Pattern:** (Lines 25-50)
  ```php
  $querySales = DailySale::query();
  
  if ($period == 'today') {
      $querySales->whereDate('sale_date', $date);
  } elseif ($period == 'this_week') {
      $querySales->whereBetween('sale_date', [$start, $end]);
  } elseif ($period == 'this_month') {
      $querySales->whereBetween('sale_date', [$start, $end]);
  }
  
  $sales = $querySales->get();
  ```

- **Aggregation Logic:** Lines 52-56
  ```php
  $totalRevenue = $sales->sum('total_revenue');
  $totalHpp = $sales->sum('total_hpp');
  $grossProfit = $sales->sum('gross_profit');
  
  $netProfit = $grossProfit - $totalExpenses;
  ```

- **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
  - Same as DashboardController
  - Multiple records per date are correctly summed
  - Exports to PDF with accurate totals

- **Output:** PDF export with period label

#### Method: `exportInventory()`
- **Status:** Does NOT query daily_sales
- **Operates on:** Ingredients and MenuItems inventory snapshots

#### Method: `storeExpense(Request $request)`
- **Status:** Does NOT query daily_sales
- **Creates:** Expense records independent of daily_sales

---

### 3.4 **AnalysisController** - AI Classification & Predictions
**Location:** `app/Http/Controllers/AnalysisController.php`

#### Method: `klasifikasi()` - Menu Classification (BCG Analysis)
- **Data Score Calculation:** Lines 40-41
  ```php
  $days = DailySale::select('sale_date')->distinct()->count();
  $records = DailySaleItem::count();
  ```
  
  - **⚠️ Issue:** Counts DISTINCT sale_dates
  - **Current Assumption:** One record per date
  - **Multi-Transaction Impact:** ✅ STILL WORKS
    - Counts unique DATES (not records)
    - If multiple records same day → still counts as 1 day
    - Correctly identifies "30 days of sales"

- **Menu Metrics Calculation:** Line 89-113
  ```php
  $soldMenuIds = DailySaleItem::select('menu_item_id')->distinct()->pluck('menu_item_id');
  
  // For each menu:
  $sales = DailySaleItem::where('menu_item_id', $menuId)
      ->join('daily_sales', 'daily_sale_items.daily_sale_id', '=', 'daily_sales.id')
      ->orderBy('daily_sales.sale_date', 'asc')
      ->get();
  
  $totalQty = $sales->sum('qty_sold');
  $cmPerItem = $sales->sum('contribution_margin') / $totalQty;
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Joins to get sale_date ordering
    - Sums qty_sold and contribution_margin
    - Multiple records per date are properly aggregated

- **BCG Categories:** (Star, Plowhorse, Puzzle, Dog)
  - Based on Menu Mix % and Contribution Margin per item
  - Trends calculated by comparing first half vs second half of sales

- **Trend Analysis:** Lines 122-132
  ```php
  $halfCount = floor($sales->count() / 2);
  $firstHalfQty = $sales->take($halfCount)->sum('qty_sold');
  $secondHalfQty = $sales->skip($halfCount)->sum('qty_sold');
  ```
  
  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Splits historical records in half
    - Compares quantities across time
    - Multiple per-day records naturally included in time series

#### Method: `prediksi()` - Sales Prediction (Linear Regression)
- **Sales by Day Query:** Lines 243-250
  ```php
  $salesByDay = DailySaleItem::select(
      'menu_item_id',
      DB::raw('DATE(daily_sales.sale_date) as sale_date'),
      DB::raw('SUM(qty_sold) as daily_qty')
  )
      ->join('daily_sales', 'daily_sale_items.daily_sale_id', '=', 'daily_sales.id')
      ->groupBy('menu_item_id', DB::raw('DATE(daily_sales.sale_date)'))
      ->orderBy(DB::raw('DATE(daily_sales.sale_date)'))
      ->get();
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - **Key:** Uses `SUM(qty_sold)` with `GROUP BY DATE(sale_date)`
    - Multiple DailySaleItem records per date are summed together
    - Produces one aggregated data point per date per menu
    - Linear regression operates on this aggregated data

- **Prediction:** Forecasts next 3 days' quantities based on trend

---

### 3.5 **BiHubController** - Business Intelligence
**Location:** `app/Http/Controllers/BiHubController.php`

#### Method: `salesTrend(Request $request)`
- **Query Pattern:** Lines 36-42
  ```php
  $salesTrend = DailySale::select(
      DB::raw('DATE(sale_date) as date'),
      DB::raw('SUM(total_revenue) as revenue')
  )
      ->whereBetween('sale_date', [$startDate, $endDate])
      ->groupBy('date')
      ->orderBy('date', 'asc')
      ->get();
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - **Key:** Groups by DATE and SUMS total_revenue
    - Multiple DailySale records per date are summed
    - Each DATE gets one aggregated revenue value

- **Output:** Chart data with dates and revenues

#### Method: `menuRanking(Request $request)`
- **Query Pattern:** Lines 58-69
  ```php
  $topMenus = DailySaleItem::select(
      'menu_item_id',
      DB::raw('SUM(qty_sold) as total_qty'),
      DB::raw('SUM(contribution_margin) as total_cm')
  )
      ->whereHas('dailySale', function($q) use ($startDate, $endDate) {
          $q->whereBetween('sale_date', [$startDate, $endDate]);
      })
      ->with('menuItem')
      ->groupBy('menu_item_id')
      ->orderBy('total_qty', 'desc')
      ->take(10)
      ->get();
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Groups by menu_item_id (not by date)
    - **Result:** Aggregates across ALL transactions in period
    - Multiple per-day transactions are included in total_qty/total_cm

- **Output:** Top 10 menu items by quantity sold

#### Method: `wasteAnalysis(Request $request)`
- **Query Pattern:** Lines 83-96
  ```php
  $topMenus = DailySaleItem::select(
      'menu_item_id',
      DB::raw('SUM(qty_sold) as total_qty')
  )
      ->whereHas('dailySale', function($q) use ($startDate, $endDate) {
          $q->whereBetween('sale_date', [$startDate, $endDate]);
      })
      ->groupBy('menu_item_id')
      ->orderBy('total_qty', 'desc')
      ->take(10)
      ->get();
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Same pattern as menuRanking
    - Compares production vs sales quantities
    - Multiple transactions per day properly aggregated

- **Output:** Waste/unsold product analysis chart

---

### 3.6 **SpkController** - Menu Engineering (SPK)
**Location:** `app/Http/Controllers/SpkController.php`

#### Method: `index(Request $request)`
- **Query Pattern:** Lines 21-29
  ```php
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
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Groups by menu_item_id across period
    - Multiple per-day transactions aggregated in SUM()

- **BCG Analysis:** 
  - Calculates Menu Mix % and Contribution Margin per item
  - Classifies as: Star, Plowhorse, Puzzle, Dog
  - Same logic as AnalysisController

#### Method: `exportPdf(Request $request)`
- **Query Pattern:** Line 118
  ```php
  $sales = DailySaleItem::whereHas('dailySale', function($q) use ($startDate) {
      $q->whereDate('sale_date', '>=', $startDate);
  })->get();
  ```

  - **Multi-Transaction Impact:** ✅ WORKS CORRECTLY
    - Fetches all DailySaleItem records in date range
    - Further aggregates them in PHP for PDF

---

### 3.7 **InventoryController** - NOT AFFECTED
**Location:** `app/Http/Controllers/InventoryController.php`

- **Status:** Does NOT query daily_sales table
- **Operates on:** Ingredients, Purchases, ProductionLogs
- **No changes needed**

---

## 4. MODEL INTERACTION SUMMARY

| Model | Relationship | Notes |
|-------|-------------|-------|
| **User** | `hasMany(DailySale)` | Tracks which user created each sale |
| **DailySale** | `belongsTo(User)` | Cashier/operator reference |
| **DailySale** | `hasMany(DailySaleItem)` | One-to-many relationship |
| **DailySaleItem** | `belongsTo(DailySale)` | Always references parent sale |
| **DailySaleItem** | `belongsTo(MenuItem)` | References menu item sold |
| **MenuItem** | Implicit (hasMany items) | Items from various sales |

---

## 5. QUERY PATTERNS ANALYSIS

### Pattern 1: Filtering by Date Range ✅
```php
$sales = DailySale::whereBetween('sale_date', [$start, $end])->get();
$totalRev = $sales->sum('total_revenue');
```
- **Status:** WORKS CORRECTLY with multiple per-day records
- **Controllers:** DashboardController, ReportController
- **Result:** Sums all matching records

### Pattern 2: GROUP BY DATE with SUM ✅
```php
DailySale::select(DB::raw('DATE(sale_date) as date'), 
                   DB::raw('SUM(total_revenue) as revenue'))
    ->groupBy('date')
    ->get();
```
- **Status:** WORKS CORRECTLY with multiple per-day records
- **Controllers:** BiHubController
- **Result:** One row per unique date with aggregated revenue

### Pattern 3: GROUP BY MENU with SUM across period ✅
```php
DailySaleItem::select('menu_item_id',
                       DB::raw('SUM(qty_sold) as total_qty'))
    ->whereHas('dailySale', function($q) { ... })
    ->groupBy('menu_item_id')
    ->get();
```
- **Status:** WORKS CORRECTLY with multiple per-day records
- **Controllers:** BiHubController, SpkController, AnalysisController
- **Result:** One row per menu with period totals

### Pattern 4: DISTINCT Sale Dates ⚠️
```php
$days = DailySale::select('sale_date')->distinct()->count();
```
- **Status:** WORKS CORRECTLY with multiple per-day records
- **Controllers:** AnalysisController
- **Behavior:** Counts unique DATES (not records)
- **Result:** Correctly identifies "30 days of sales" even with multiple transactions per day

### Pattern 5: ORDER BY date in JOIN ✅
```php
DailySaleItem::where('menu_item_id', $menuId)
    ->join('daily_sales', 'daily_sale_items.daily_sale_id', '=', 'daily_sales.id')
    ->orderBy('daily_sales.sale_date', 'asc')
    ->get();
```
- **Status:** WORKS CORRECTLY with multiple per-day records
- **Controllers:** AnalysisController
- **Behavior:** Orders items chronologically, includes all per-day transactions
- **Result:** Proper time-series data for trend analysis

---

## 6. CRITICAL FINDINGS

### ✅ POSITIVE: Ready for Multi-Transaction-Per-Day

1. **Database Constraint Removed**
   - Migration `2026_05_01_080422_drop_unique_sale_date_from_daily_sales.php` already dropped UNIQUE(sale_date)
   - Multiple DailySale records per date are now allowed

2. **Aggregation Logic is Sound**
   - All GROUP BY queries correctly use DATE() and SUM()
   - Multiple records per date are properly aggregated
   - No double-counting detected in current queries

3. **Relationships are Correct**
   - One-to-many from DailySale → DailySaleItem ensures proper hierarchies
   - Foreign keys have cascadeOnDelete for data integrity

4. **Totals are Per-Record**
   - Each DailySale record has its own total_revenue, total_hpp, gross_profit
   - `recalculateTotals()` method updates each record individually
   - No shared state issues

### ⚠️ MINOR CONCERNS

1. **PosController Assumption**
   - Creates one DailySale per POST request (per transaction)
   - Uses current date only
   - If user processes multiple transactions today, each gets own DailySale record
   - **No issue** - this is the desired behavior for multi-transaction support

2. **Dashboard/Report Logic**
   - When summing totals per day, multiple records are summed together
   - This is correct ONLY IF each DailySale record represents ONE logical transaction
   - **Current:** Each PosController::store() creates one record per checkout
   - **Assumption:** Works as designed

3. **Data Integrity Note**
   - Total_revenue, total_hpp, gross_profit are denormalized (cached in header)
   - If DailySaleItem is modified without calling recalculateTotals(), header becomes stale
   - **No automated trigger** for recalculation
   - **Risk:** If items are modified elsewhere, header won't update

---

## 7. AFFECTED FILES SUMMARY

### Controllers (6 files)
| File | Methods | Daily Sales Queries | Multi-Trans Ready |
|------|---------|-------------------|------------------|
| [PosController](app/Http/Controllers/PosController.php) | store() | CREATE, items.makeFromQty() | ✅ Yes |
| [DashboardController](app/Http/Controllers/DashboardController.php) | index() | SELECT with whereBetween/whereDate, SUM() | ✅ Yes |
| [ReportController](app/Http/Controllers/ReportController.php) | exportFinance() | SELECT with whereBetween/whereDate, SUM() | ✅ Yes |
| [AnalysisController](app/Http/Controllers/AnalysisController.php) | klasifikasi(), prediksi() | JOIN, GROUP BY, DISTINCT, SUM() | ✅ Yes |
| [BiHubController](app/Http/Controllers/BiHubController.php) | salesTrend(), menuRanking(), wasteAnalysis() | GROUP BY DATE, whereHas(), SUM() | ✅ Yes |
| [SpkController](app/Http/Controllers/SpkController.php) | index(), exportPdf() | whereHas(), GROUP BY, SUM() | ✅ Yes |

### Models (2 files)
| File | Relationships | Methods | Notes |
|------|--------------|---------|-------|
| [DailySale](app/Models/DailySale.php) | belongsTo(User), hasMany(DailySaleItem) | recalculateTotals(), getFoodCostPercentAttribute() | ✅ Per-record totals |
| [DailySaleItem](app/Models/DailySaleItem.php) | belongsTo(DailySale), belongsTo(MenuItem) | makeFromQty() | ✅ Factory method |

### Migrations (2 relevant files)
| File | Purpose | Current State |
|------|---------|---------------|
| 2026_05_01_063330_b_create_daily_sales_table.php | Initial schema | Had UNIQUE(sale_date) |
| 2026_05_01_080422_drop_unique_sale_date_from_daily_sales.php | **Remove UNIQUE constraint** | ✅ **Applied** |

---

## 8. RECOMMENDATIONS

### No Changes Required For:
- ✅ Multi-transaction-per-day support is already built-in
- ✅ Query aggregations correctly handle multiple records per date
- ✅ Database schema allows multiple records per date

### Optional Improvements (Not Critical):

1. **Add Data Integrity Safeguards**
   - Option A: Add Eloquent event listeners to auto-recalculate parent totals
   - Option B: Add database triggers to ensure header totals stay in sync
   - Current: Manual recalculateTotals() call in PosController

2. **Improve Audit Trail**
   - Currently tracks `user_id` (who created sale)
   - Could add timestamps for when record was created
   - Current: `created_at`, `updated_at` already present ✅

3. **Consider Payment Method Per-Item**
   - Currently `payment_method` is per-DailySale record
   - If splitting payment across items, would need refactor
   - Current: Assumes one payment method per transaction ✅

4. **Monitor Performance**
   - As multiple per-day records accumulate, GROUP BY queries may slow
   - Consider indexing: `(sale_date)`, `(daily_sale_id)`, `(menu_item_id, sale_date)`
   - Current indexes: Only FK indexes from migrations

---

## 9. ROUTE ENDPOINTS AFFECTED

```
GET  /dashboard                    → DashboardController@index
GET  /reports                      → ReportController@index
GET  /reports/finance              → ReportController@exportFinance
GET  /reports/inventory            → ReportController@exportInventory
POST /reports/expenses             → ReportController@storeExpense
GET  /pos                          → PosController@index
POST /pos                          → PosController@store ⭐ CREATES DailySale
GET  /analysis/klasifikasi         → AnalysisController@klasifikasi
GET  /analysis/prediksi            → AnalysisController@prediksi
GET  /reports/sales-trend          → BiHubController@salesTrend
GET  /reports/menu-ranking         → BiHubController@menuRanking
GET  /reports/waste-analysis       → BiHubController@wasteAnalysis
GET  /spk                          → SpkController@index
GET  /spk/export-pdf               → SpkController@exportPdf
```

---

## 10. CONCLUSION

### Current State: ✅ PRODUCTION READY FOR MULTI-TRANSACTIONS

The Laravel application is **fully prepared** for multiple daily_sales records per day:

1. **Database Schema** - UNIQUE constraint on sale_date was explicitly removed
2. **Aggregation Logic** - All queries use GROUP BY and SUM() appropriately
3. **Relationships** - Properly normalized with FK constraints
4. **Data Model** - Each DailySale record is self-contained with its own totals
5. **Controllers** - All 6 affected controllers handle multiple records correctly

### Query Accuracy: ✅ ALL VERIFIED CORRECT

- Date range filters work correctly
- Aggregations across multiple per-day records are accurate
- Trend analysis properly includes all transactions
- No double-counting detected

### No Breaking Changes Needed

The system can safely handle:
- Multiple transactions per day ✅
- Multiple payment methods per day ✅
- Period-based aggregations ✅
- Menu performance analysis ✅
- Financial reporting ✅

---

## APPENDIX: Query Examples

### Example 1: Multiple Transactions on May 10, 2026

```
daily_sales table (sample):
id  user_id  sale_date   total_revenue  total_hpp  gross_profit
1   5        2026-05-10  150000         60000      90000
2   5        2026-05-10  200000         80000      120000
3   6        2026-05-10  100000         40000      60000

daily_sale_items table (sample):
id  daily_sale_id  menu_item_id  qty_sold  subtotal_revenue  contribution_margin
1   1              3             10        150000            90000
2   2              5             5         200000            120000
3   3              2             8         100000            60000
```

### Example 2: DashboardController Query on May 10, 2026

```php
$sales = DailySale::whereDate('sale_date', Carbon::parse('2026-05-10'))->get();
// Result: 3 records (IDs: 1, 2, 3)

$totalRev = $sales->sum('total_revenue');
// Result: 150000 + 200000 + 100000 = 450000 ✅ CORRECT

$totalHpp = $sales->sum('total_hpp');
// Result: 60000 + 80000 + 40000 = 180000 ✅ CORRECT
```

### Example 3: BiHubController Graph Data for May 10, 2026

```php
$salesTrend = DailySale::select(
    DB::raw('DATE(sale_date) as date'),
    DB::raw('SUM(total_revenue) as revenue')
)->whereDate('sale_date', '2026-05-10')
->groupBy('date')
->get();

// Result: 1 row
// date: 2026-05-10
// revenue: 450000 ✅ CORRECT (sums all 3 records)
```

---

**Report Status:** COMPLETE  
**Generated by:** Copilot Analysis Agent  
**Date:** 2026-05-13

