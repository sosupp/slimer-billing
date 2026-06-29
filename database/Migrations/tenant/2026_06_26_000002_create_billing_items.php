<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_id');
            
            // Polymorphic relationship for the item source
            $table->nullableMorphs('itemable'); // Can link to products, services, fee structures, etc.
            
            // Item details (denormalized for historical accuracy)
            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('total', 15, 2);
            
            // Additional fields
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('billing_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_items');
    }
};