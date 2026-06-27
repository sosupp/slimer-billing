<?php

namespace Sosupp\SlimerBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sosupp\SlimerBilling\Traits\BillingRelations;
use YourVendor\SchoolFeeBilling\Traits\HasBillingItems;

class Billing extends Model
{
    use SoftDeletes, BillingRelations, HasBillingItems;

    protected $table = 'billings';

    protected $fillable = [
        'billable_type', 'billable_id', 'billing_number', 'title',
        'description', 'billing_date', 'due_date', 'period_start',
        'period_end', 'status', 'type', 'subtotal',
        'discount_amount', 'discount_type', 'discount_percentage', 'tax_amount',
        'tax_rate', 'shipping_amount', 'adjustment_amount', 'adjustment_reason',
        'total', 'amount_paid', 'balance_due', 'currency',
        'exchange_rate', 'metadata', 'notes', 'terms_and_conditions',
        'created_by', 'updated_by', 'approved_by', 'approved_at',
        'sent_at', 'paid_at'
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'metadata' => 'json',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billing) {
            if (empty($billing->billing_number)) {
                $billing->billing_number = static::generateBillingNumber();
            }
            if (empty($billing->currency)) {
                $billing->currency = config('slimerbilling.currency.default', 'USD');
            }
        });

        static::saving(function ($billing) {
            $billing->calculateBalanceDue();
        });
    }

    /**
     * Generate a unique billing number.
     */
    public static function generateBillingNumber(): string
    {
        $prefix = config('slimerbilling.billing_number_prefix', 'INV');
        $year = date('Y');
        $month = date('m');
        
        $lastBilling = static::whereYear('billing_date', $year)
            ->whereMonth('billing_date', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastBilling ? intval(substr($lastBilling->billing_number, -4)) + 1 : 1;
        
        return $prefix . '-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all payments for this billing.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    /**
     * Get the user who created this billing.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'created_by');
    }

    /**
     * Get the user who updated this billing.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'updated_by');
    }

    /**
     * Get the user who approved this billing.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'approved_by');
    }

    /**
     * Calculate totals for the billing.
     */
    public function calculateTotals(): self
    {
        $this->subtotal = $this->items->sum('total');
        
        // Calculate discount
        if ($this->discount_type === 'percentage' && $this->discount_percentage) {
            $this->discount_amount = $this->subtotal * ($this->discount_percentage / 100);
        }
        
        // Calculate tax
        if ($this->tax_rate) {
            $this->tax_amount = ($this->subtotal - $this->discount_amount) * ($this->tax_rate / 100);
        }
        
        $this->total = $this->subtotal 
            - $this->discount_amount 
            + $this->tax_amount 
            + $this->shipping_amount 
            + $this->adjustment_amount;
        
        $this->calculateBalanceDue();
        
        return $this;
    }

    /**
     * Calculate balance due.
     */
    public function calculateBalanceDue(): self
    {
        $this->balance_due = $this->total - $this->amount_paid;
        return $this;
    }

    /**
     * Apply a payment to this billing.
     */
    public function applyPayment(BillingPayment $payment): self
    {
        $this->amount_paid += $payment->amount;
        $this->calculateBalanceDue();
        
        if ($this->balance_due <= 0 && $this->status !== 'cancelled') {
            $this->status = 'paid';
            $this->paid_at = now();
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Check if billing is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') {
            return false;
        }
        
        return $this->due_date && $this->due_date->isPast() && $this->balance_due > 0;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'published' => 'primary',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'dark',
            'refunded' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'credit_note' => 'Credit Note',
            'debit_note' => 'Debit Note',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total, 2);
    }

    /**
     * Get formatted balance due.
     */
    public function getFormattedBalanceDueAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->balance_due, 2);
    }

    /**
     * Scope a query to filter by billable type.
     */
    public function scopeBillableType($query, string $type)
    {
        return $query->where('billable_type', $type);
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
        return $query->whereBetween('billing_date', [$start, $end]);
    }

    /**
     * Scope a query to get overdue billings.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now())
            ->where('balance_due', '>', 0);
    }
}