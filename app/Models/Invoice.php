<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model {
    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'loyalty_discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'round_off' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items(): HasMany {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany {
        return $this->hasMany(InvoicePayment::class);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function table(): BelongsTo {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }
}
