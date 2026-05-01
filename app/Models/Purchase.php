<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['user_id', 'supplier_id', 'purchase_date', 'total_amount', 'payment_method', 'notes'];

    protected $casts = ['purchase_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items->sum('subtotal');
        $this->save();
    }
}
