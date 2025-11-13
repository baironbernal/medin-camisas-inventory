<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('attribute_id')->nullable()->constrained('attributes');
            $table->foreignId('attribute_value_id')->nullable()->constrained('attribute_values');
            $table->enum('modifier_type', ['percentage', 'fixed_amount']);
            $table->decimal('modifier_value', 10, 2);
            $table->integer('priority')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_rules');
    }
};


