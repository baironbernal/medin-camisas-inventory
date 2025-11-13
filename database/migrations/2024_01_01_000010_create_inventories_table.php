<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('store_id')->constrained('stores');
            $table->integer('quantity_available')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_in_transit')->default(0);
            $table->integer('min_quantity')->nullable();
            $table->integer('max_quantity')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->string('location')->nullable();
            $table->date('last_restock_date')->nullable();
            $table->date('last_sale_date')->nullable();
            $table->date('last_inventory_check_date')->nullable();
            $table->timestamps();

            $table->unique(['product_variant_id', 'store_id']);
            $table->index(['store_id', 'quantity_available']);
            $table->index('quantity_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};


