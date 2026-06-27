<?php

namespace Sosupp\SlimerBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingLineItem extends Model
{
    protected $table = 'billing_line_items';

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_price',
        'unit',
        'category',
        'metadata',
        'is_active'
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get all billing items using this line item.
     */
    public function billingItems(): HasMany
    {
        return $this->hasMany(BillingItem::class, 'itemable_id')
            ->where('itemable_type', self::class);
    }
}