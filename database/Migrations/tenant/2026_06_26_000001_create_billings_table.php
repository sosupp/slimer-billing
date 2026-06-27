<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship fields
            $table->morphs('billable'); // This creates billable_type and billable_id
            
            $table->string('billing_number')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            $table->date('billing_date')->nullable();
            $table->date('due_date')->nullable();
            
            // Billing period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Status and type
            $table->enum('status', [
                'draft', 'published', 'sent', 'paid', 'overdue', 'cancelled', 'refunded'
            ])->default('draft');

            $table->enum('type', [
                'invoice', 'receipt', 'credit_note', 'debit_note'
            ])->default('invoice');
            
            // Financial fields
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('discount_type')->nullable(); // 'percentage', 'fixed'
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->decimal('adjustment_amount', 15, 2)->default(0);
            $table->string('adjustment_reason')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            
            // Currency
            $table->string('currency')->default('GHS');
            $table->decimal('exchange_rate', 10, 4)->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            
            // Soft delete
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['billable_type', 'billable_id']);
            $table->index('billing_number');
            $table->index('status');
            $table->index('billing_date');
            $table->index('due_date');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};