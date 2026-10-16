<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model {
    protected $guarded = [];

    protected $casts = [
        'is_veg' => 'boolean',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
        'tax_percent' => 'decimal:2',
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany {
        return $this->hasMany(ProductVariant::class);
    }

    public function addons(): BelongsToMany {
        return $this->belongsToMany(Addon::class, 'product_addon');
    }

    public function recipes(): HasMany {
        return $this->hasMany(Recipe::class);
    }
}
