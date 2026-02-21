<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->timestamps();
            $table->index('sku');
            $table->index('barcode');
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};


