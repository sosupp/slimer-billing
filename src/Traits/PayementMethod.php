<?php

namespace Sosupp\SlimerBilling\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Sosupp\SlimerBilling\Models\BillingPayment;

trait PaymentMethod
{
    /**
     * Get all payments using this payment method.
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(
            config('slimerbilling.models.billing_payment', BillingPayment::class), 
            'payment_method'
        );
    }

    /**
     * Get total amount processed by this payment method.
     */
    public function getTotalProcessedAttribute(): float
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get total refunds processed by this payment method.
     */
    public function getTotalRefundedAttribute(): float
    {
        return $this->payments()->where('status', 'refunded')->sum('amount');
    }

    /**
     * Get net amount processed (completed - refunded).
     */
    public function getNetProcessedAttribute(): float
    {
        return $this->total_processed - $this->total_refunded;
    }

    /**
     * Get payment statistics.
     */
    public function getPaymentStatistics(): array
    {
        $stats = $this->payments()
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_processed,
                SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as total_refunded,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
                AVG(amount) as average_amount
            ')
            ->first();

        return [
            'total_transactions' => (int) ($stats->total_transactions ?? 0),
            'total_processed' => (float) ($stats->total_processed ?? 0),
            'total_refunded' => (float) ($stats->total_refunded ?? 0),
            'failed_count' => (int) ($stats->failed_count ?? 0),
            'average_amount' => (float) ($stats->average_amount ?? 0),
            'success_rate' => $stats->total_transactions > 0 
                ? (($stats->total_transactions - $stats->failed_count) / $stats->total_transactions) * 100 
                : 0,
        ];
    }

    /**
     * Get payments for a specific date range.
     */
    public function getPaymentsForPeriod($startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->payments()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();
    }

    /**
     * Get total amount for a specific date range.
     */
    public function getTotalForPeriod($startDate, $endDate): float
    {
        return $this->payments()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Check if this payment method is active.
     */
    public function isActive(): bool
    {
        // You can override this in your model
        return $this->active ?? true;
    }

    /**
     * Get recent transactions.
     */
    public function getRecentTransactions($limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->payments()
            ->with('billing')
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payment method usage frequency.
     */
    public function getUsageFrequency(): array
    {
        $last30Days = $this->payments()
            ->where('payment_date', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->count();

        $last90Days = $this->payments()
            ->where('payment_date', '>=', now()->subDays(90))
            ->where('status', 'completed')
            ->count();

        return [
            'last_30_days' => $last30Days,
            'last_90_days' => $last90Days,
            'total_uses' => $this->payments()->where('status', 'completed')->count(),
        ];
    }
}