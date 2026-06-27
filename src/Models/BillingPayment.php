<?php

namespace Sosupp\SlimerBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPayment extends Model
{
    protected $table = 'billing_payments';

    protected $fillable = [
        'billing_id',
        'payment_method_type',
        'payment_method_id',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payment_date',
        'notes',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'metadata' => 'json',
    ];

    /**
     * Get the parent billing.
     */
    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    /**
     * Get the parent payment method (polymorphic).
     */
    public function paymentMethod(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config(
            'auth.providers.users.model', \App\Models\User::class), 'created_by'
        );
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('payment_date', [$start, $end]);
    }
}