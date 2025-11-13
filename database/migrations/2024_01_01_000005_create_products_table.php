<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('season_id')->constrained('seasons');
            $table->foreignId('category_id')->constrained('categories');
            $table->decimal('base_price', 10, 2);
            $table->decimal('cost', 10, 2);
            $table->string('brand')->nullable();
            $table->string('supplier')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->json('specifications')->nullable();
            $table->timestamps();

            $table->index('reference_code');
            $table->index('slug');
            $table->index(['season_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


