<?php

namespace Sosupp\SlimerBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingItem extends Model
{
    protected $table = 'billing_items';

    protected $fillable = [
        'billing_id',
        'itemable_type',
        'itemable_id',
        'item_name',
        'item_code',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'tax_rate',
        'total',
        'metadata',
        'sort_order'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'json',
        'sort_order' => 'integer'
    ];

    /**
     * Get the parent billing.
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    /**
     * Get the parent itemable model (polymorphic).
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate the total for this item.
     */
    public function calculateTotal(): self
    {
        $subtotal = $this->unit_price * $this->quantity;
        
        // Calculate discount
        if ($this->discount_percentage) {
            $this->discount_amount = $subtotal * ($this->discount_percentage / 100);
        }
        
        // Calculate tax
        if ($this->tax_rate) {
            $this->tax_amount = ($subtotal - $this->discount_amount) * ($this->tax_rate / 100);
        }
        
        $this->total = $subtotal - $this->discount_amount + $this->tax_amount;
        
        return $this;
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        $currency = $this->billing->currency ?? config('slimerbilling.currency.default', 'USD');
        return $currency . ' ' . number_format($this->total, 2);
    }
}