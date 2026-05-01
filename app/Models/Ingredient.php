<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name', 'unit', 'current_stock', 'min_stock',
        'cost_per_unit', 'supplier_id', 'notes', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Apakah stok di bawah minimum?
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    /**
     * Hitung ulang cost_per_unit menggunakan Moving Average
     * dipanggil setiap ada pembelian baru
     */
    public function recalculateMovingAverage(): void
    {
        $lastPrices = $this->prices()->latest('purchased_at')->take(5)->get();
        if ($lastPrices->isNotEmpty()) {
            $this->cost_per_unit = $lastPrices->avg('price_per_unit');
            $this->save();
        }
    }
}
