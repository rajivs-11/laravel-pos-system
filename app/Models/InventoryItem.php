<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model {
    protected $guarded = [];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function stockMovements(): HasMany {
        return $this->hasMany(StockMovement::class);
    }

    public function recipes(): HasMany {
        return $this->hasMany(Recipe::class);
    }

    public function getIsLowStockAttribute(): bool {
        return $this->current_stock <= $this->reorder_level;
    }
}
