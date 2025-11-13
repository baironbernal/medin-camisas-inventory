<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'purchase',
                'sale',
                'transfer',
                'adjustment',
                'return',
                'damage',
                'production'
            ]);
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('destination_store_id')->nullable()->constrained('stores');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_document')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('supplier_id')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index(['product_variant_id', 'store_id']);
            $table->index(['store_id', 'created_at']);
            $table->index('reference_document');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};


