<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_id');
            
            // Polymorphic payment method
            $table->nullableMorphs('payment_method');
            
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');

            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'refunded'
            ])->default('pending');
            
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['billing_id', 'status']);
            $table->index('transaction_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
    }
};