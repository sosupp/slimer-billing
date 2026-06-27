<?php

namespace Sosupp\SlimerBilling\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Sosupp\SlimerBilling\Models\Billing;
use Sosupp\SlimerBilling\Models\BillingPayment;
use Sosupp\SlimerBilling\Services\BillingService;

trait Billable
{
    /**
     * Get all billings for this model.
     */
    public function billings(): MorphMany
    {
        return $this->morphMany(config('slimerbilling.models.billing', Billing::class), 'billable');
    }

    /**
     * Get all payments for this model.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(config(
            'slimerbilling.models.billing_payment', BillingPayment::class), 'payment_method'
        );
    }

    /**
     * Get all paid billings.
     */
    public function paidBillings(): MorphMany
    {
        return $this->billings()->where('status', 'paid');
    }

    /**
     * Get all pending billings.
     */
    public function pendingBillings(): MorphMany
    {
        return $this->billings()->whereIn('status', ['draft', 'published', 'sent']);
    }

    /**
     * Get all overdue billings.
     */
    public function overdueBillings(): MorphMany
    {
        return $this->billings()->overdue();
    }

    /**
     * Get total amount billed for this model.
     */
    public function getTotalBilledAttribute(): float
    {
        return $this->billings()->sum('total');
    }

    /**
     * Get total amount paid for this model.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->billings()->sum('amount_paid');
    }

    /**
     * Get total balance due for this model.
     */
    public function getTotalBalanceDueAttribute(): float
    {
        return $this->billings()->sum('balance_due');
    }

    /**
     * Get billing statistics for this model.
     */
    public function getBillingStatistics(): array
    {
        $stats = $this->billings()
            ->selectRaw('
                COUNT(*) as total_billings,
                SUM(total) as total_amount,
                SUM(amount_paid) as total_paid,
                SUM(balance_due) as total_balance,
                COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_count,
                COUNT(CASE WHEN status IN ("draft", "published", "sent") THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = "overdue" THEN 1 END) as overdue_count,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_count
            ')
            ->first();

        return [
            'total_billings' => (int) ($stats->total_billings ?? 0),
            'total_amount' => (float) ($stats->total_amount ?? 0),
            'total_paid' => (float) ($stats->total_paid ?? 0),
            'total_balance' => (float) ($stats->total_balance ?? 0),
            'paid_count' => (int) ($stats->paid_count ?? 0),
            'pending_count' => (int) ($stats->pending_count ?? 0),
            'overdue_count' => (int) ($stats->overdue_count ?? 0),
            'cancelled_count' => (int) ($stats->cancelled_count ?? 0),
        ];
    }

    /**
     * Check if this model has any billings.
     */
    public function hasBillings(): bool
    {
        return $this->billings()->exists();
    }

    /**
     * Check if this model has any overdue billings.
     */
    public function hasOverdueBillings(): bool
    {
        return $this->billings()->overdue()->exists();
    }

    /**
     * Create a new billing for this model.
     */
    public function createBilling(array $data): Billing
    {
        $data['billable_type'] = get_class($this);
        $data['billable_id'] = $this->getKey();
        
        return app(BillingService::class)->createBilling($data);
    }

    /**
     * Create multiple billings for this model.
     */
    public function createMultipleBillings(array $billingsData): array
    {
        $created = [];
        
        foreach ($billingsData as $data) {
            $created[] = $this->createBilling($data);
        }
        
        return $created;
    }

    /**
     * Get the latest billing for this model.
     */
    public function getLatestBilling(): ?Billing
    {
        return $this->billings()->latest()->first();
    }

    /**
     * Get billings by status.
     */
    public function getBillingsByStatus(string $status): Collection
    {
        return $this->billings()->where('status', $status)->get();
    }

    /**
     * Get billings by date range.
     */
    public function getBillingsByDateRange($startDate, $endDate): Collection
    {
        return $this->billings()
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get total billings amount for a specific period.
     */
    public function getTotalBilledForPeriod($startDate, $endDate): float
    {
        return $this->billings()
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->sum('total');
    }

    /**
     * Get billing summary grouped by status.
     */
    public function getBillingSummaryByStatus(): array
    {
        return $this->billings()
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();
    }

    /**
     * Check if billings are fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->billings()
            ->where('status', '!=', 'paid')
            ->where('balance_due', '>', 0)
            ->doesntExist();
    }

    /**
     * Get overdue billings that need immediate attention.
     */
    public function getCriticalOverdueBillings(): Collection
    {
        return $this->billings()
            ->overdue()
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Sync this model as billable to a billing.
     */
    public function syncToBilling(Billing $billing): void
    {
        if ($billing->billable_type !== get_class($this) || $billing->billable_id !== $this->getKey()) {
            // This shouldn't happen, but just in case
            throw new \InvalidArgumentException('This model does not belong to the billing.');
        }
        
        // Update billing with current model data
        $billing->update([
            'billable_type' => get_class($this),
            'billable_id' => $this->getKey(),
        ]);
    }
}