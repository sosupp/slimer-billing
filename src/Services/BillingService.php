<?php

namespace Sosupp\SlimerBilling\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Sosupp\SlimerBilling\Models\Billing;
use Sosupp\SlimerBilling\Models\BillingItem;
use Sosupp\SlimerBilling\Models\BillingPayment;

class BillingService
{
    /**
     * Create a new billing.
     */
    public function createBilling(array $data): Billing
    {
        DB::beginTransaction();
        
        try {
            // Validate billable relationship
            if (!isset($data['billable_type']) || !isset($data['billable_id'])) {
                throw new \Exception('Billable type and ID are required.');
            }
            
            $billing = Billing::create([
                'billable_type' => $data['billable_type'],
                'billable_id' => $data['billable_id'],
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'billing_date' => $data['billing_date'] ?? now(),
                'due_date' => $data['due_date'] ?? null,
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'status' => $data['status'] ?? config('slimerbilling.default_status', 'draft'),
                'type' => $data['type'] ?? 'invoice',
                'discount_type' => $data['discount_type'] ?? null,
                'discount_percentage' => $data['discount_percentage'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'shipping_amount' => $data['shipping_amount'] ?? 0,
                'adjustment_amount' => $data['adjustment_amount'] ?? 0,
                'adjustment_reason' => $data['adjustment_reason'] ?? null,
                'currency' => $data['currency'] ?? config('slimerbilling.currency.default', 'USD'),
                'metadata' => $data['metadata'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'created_by' => Auth::id()
            ]);

            // Create billing items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addItemToBilling($billing, $itemData);
                }
            }

            // Calculate totals
            $billing->calculateTotals();
            $billing->save();

            DB::commit();

            return $billing->fresh(['items', 'billable']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add an item to a billing.
     */
    public function addItemToBilling(Billing $billing, array $itemData): BillingItem
    {
        $item = new BillingItem([
            'itemable_type' => $itemData['itemable_type'] ?? null,
            'itemable_id' => $itemData['itemable_id'] ?? null,
            'item_name' => $itemData['item_name'],
            'item_code' => $itemData['item_code'] ?? null,
            'description' => $itemData['description'] ?? null,
            'quantity' => $itemData['quantity'] ?? 1,
            'unit_price' => $itemData['unit_price'],
            'discount_percentage' => $itemData['discount_percentage'] ?? null,
            'discount_amount' => $itemData['discount_amount'] ?? 0,
            'tax_rate' => $itemData['tax_rate'] ?? 0,
            'metadata' => $itemData['metadata'] ?? null,
            'sort_order' => $itemData['sort_order'] ?? $billing->items->count()
        ]);

        $item->calculateTotal();
        $billing->items()->save($item);

        return $item;
    }

    /**
     * Update an existing billing.
     */
    public function updateBilling(Billing $billing, array $data): Billing
    {
        DB::beginTransaction();

        try {
            // Update billing details
            $billing->update([
                'title' => $data['title'] ?? $billing->title,
                'description' => $data['description'] ?? $billing->description,
                'billing_date' => $data['billing_date'] ?? $billing->billing_date,
                'due_date' => $data['due_date'] ?? $billing->due_date,
                'period_start' => $data['period_start'] ?? $billing->period_start,
                'period_end' => $data['period_end'] ?? $billing->period_end,
                'discount_type' => $data['discount_type'] ?? $billing->discount_type,
                'discount_percentage' => $data['discount_percentage'] ?? $billing->discount_percentage,
                'discount_amount' => $data['discount_amount'] ?? $billing->discount_amount,
                'tax_rate' => $data['tax_rate'] ?? $billing->tax_rate,
                'shipping_amount' => $data['shipping_amount'] ?? $billing->shipping_amount,
                'adjustment_amount' => $data['adjustment_amount'] ?? $billing->adjustment_amount,
                'adjustment_reason' => $data['adjustment_reason'] ?? $billing->adjustment_reason,
                'metadata' => $data['metadata'] ?? $billing->metadata,
                'notes' => $data['notes'] ?? $billing->notes,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? $billing->terms_and_conditions,
            ]);

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $this->syncBillingItems($billing, $data['items']);
            }

            // Recalculate totals
            $billing->calculateTotals();
            $billing->save();

            DB::commit();

            return $billing->fresh(['items', 'billable']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sync billing items (add, update, delete).
     */
    public function syncBillingItems(Billing $billing, array $items): void
    {
        $existingItemIds = $billing->items->pluck('id')->toArray();
        $updatedItemIds = [];

        foreach ($items as $itemData) {
            if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                // Update existing item
                $item = BillingItem::find($itemData['id']);
                $this->updateBillingItem($item, $itemData);
                $updatedItemIds[] = $item->id;
            } else {
                // Create new item
                $item = $this->addItemToBilling($billing, $itemData);
                $updatedItemIds[] = $item->id;
            }
        }

        // Delete items that were removed
        $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
        if (!empty($itemsToDelete)) {
            BillingItem::whereIn('id', $itemsToDelete)->delete();
        }
    }

    /**
     * Update a billing item.
     */
    public function updateBillingItem(BillingItem $item, array $data): BillingItem
    {
        $item->update([
            'itemable_type' => $data['itemable_type'] ?? $item->itemable_type,
            'itemable_id' => $data['itemable_id'] ?? $item->itemable_id,
            'item_name' => $data['item_name'] ?? $item->item_name,
            'item_code' => $data['item_code'] ?? $item->item_code,
            'description' => $data['description'] ?? $item->description,
            'quantity' => $data['quantity'] ?? $item->quantity,
            'unit_price' => $data['unit_price'] ?? $item->unit_price,
            'discount_percentage' => $data['discount_percentage'] ?? $item->discount_percentage,
            'discount_amount' => $data['discount_amount'] ?? $item->discount_amount,
            'tax_rate' => $data['tax_rate'] ?? $item->tax_rate,
            'metadata' => $data['metadata'] ?? $item->metadata,
            'sort_order' => $data['sort_order'] ?? $item->sort_order
        ]);

        $item->calculateTotal();
        $item->save();

        return $item;
    }

    /**
     * Record a payment for a billing.
     */
    public function recordPayment(Billing $billing, array $data): BillingPayment
    {
        DB::beginTransaction();

        try {
            $payment = BillingPayment::create([
                'billing_id' => $billing->id,
                'payment_method_type' => $data['payment_method_type'],
                'payment_method_id' => $data['payment_method_id'],
                'transaction_id' => $data['transaction_id'] ?? $this->generateTransactionId(),
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $billing->currency,
                'status' => $data['status'] ?? 'completed',
                'payment_date' => $data['payment_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => Auth::id()
            ]);

            // Apply payment to billing
            $billing->applyPayment($payment);

            DB::commit();

            return $payment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a transaction ID.
     */
    protected function generateTransactionId(): string
    {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Duplicate a billing.
     */
    public function duplicateBilling(Billing $billing, array $overrides = []): Billing
    {
        DB::beginTransaction();

        try {
            // Create new billing with same data
            $newBilling = $billing->replicate();
            $newBilling->billing_number = Billing::generateBillingNumber();
            $newBilling->status = config('slimerbilling.default_status', 'draft');
            $newBilling->approved_by = null;
            $newBilling->approved_at = null;
            $newBilling->sent_at = null;
            $newBilling->paid_at = null;
            $newBilling->amount_paid = 0;
            $newBilling->balance_due = 0;
            
            // Apply overrides
            foreach ($overrides as $key => $value) {
                $newBilling->$key = $value;
            }
            
            $newBilling->save();

            // Duplicate items
            foreach ($billing->items as $item) {
                $newItem = $item->replicate();
                $newItem->billing_id = $newBilling->id;
                $newItem->save();
            }

            // Recalculate totals
            $newBilling->calculateTotals();
            $newBilling->save();

            DB::commit();

            return $newBilling->fresh(['items', 'billable']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get billings for a specific billable entity.
     */
    public function getBillingsForEntity($entity): \Illuminate\Database\Eloquent\Collection
    {
        if (!method_exists($entity, 'billings')) {
            throw new \Exception('Entity does not have a billings relationship.');
        }
        
        return $entity->billings()->with(['items', 'payments'])->get();
    }

    /**
     * Get billing statistics for an entity.
     */
    public function getEntityStatistics($entity): array
    {
        $billings = $this->getBillingsForEntity($entity);
        
        return [
            'total_billings' => $billings->count(),
            'total_amount' => $billings->sum('total'),
            'total_paid' => $billings->sum('amount_paid'),
            'total_balance' => $billings->sum('balance_due'),
            'status_breakdown' => $billings->groupBy('status')->map->count(),
            'pending_count' => $billings->where('status', 'published')->count(),
            'overdue_count' => $billings->filter->isOverdue()->count(),
            'paid_count' => $billings->where('status', 'paid')->count(),
        ];
    }

    /**
     * Bulk create billings for multiple entities.
     */
    public function bulkCreateBillings(array $entities, array $baseData): array
    {
        $billings = [];
        
        foreach ($entities as $entity) {
            $data = array_merge($baseData, [
                'billable_type' => get_class($entity),
                'billable_id' => $entity->id,
            ]);
            
            $billings[] = $this->createBilling($data);
        }
        
        return $billings;
    }

    /**
     * Send billing notifications.
     */
    public function sendBillingNotification(Billing $billing, array $channels = ['email']): void
    {
        // Implementation would depend on your notification system
        // You can dispatch a job or send email/SMS notifications
    }
}