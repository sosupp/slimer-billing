<?php

namespace YourVendor\SchoolFeeBilling\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Sosupp\SlimerBilling\Models\BillingItem;

trait HasBillingItems
{
    /**
     * Get all billing items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            config('slimerbilling.models.billing_item',
            BillingItem::class)
        );
    }

    /**
     * Get all items with their itemable entities.
     */
    public function itemsWithEntities()
    {
        return $this->items()->with('itemable')->get();
    }

    /**
     * Get items grouped by type.
     */
    public function getItemsGroupedByType(): array
    {
        $grouped = [];
        
        foreach ($this->items as $item) {
            $type = class_basename($item->itemable_type);
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $item;
        }
        
        return $grouped;
    }

    /**
     * Get items summary.
     */
    public function getItemsSummary(): array
    {
        return [
            'total_items' => $this->items->count(),
            'total_quantity' => $this->items->sum('quantity'),
            'total_amount' => $this->items->sum('total'),
            'categories' => $this->items->groupBy('item_name')->map(function ($group) {
                return [
                    'quantity' => $group->sum('quantity'),
                    'total' => $group->sum('total'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get items by category.
     */
    public function getItemsByCategory(string $category)
    {
        return $this->items->filter(function ($item) use ($category) {
            return $item->metadata && isset($item->metadata['category']) 
                && $item->metadata['category'] === $category;
        });
    }
}