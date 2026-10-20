<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            // Hashed client IP — lets us allow one review per visitor per product
            // without storing the raw IP address.
            $table->string('ip_hash', 64)->nullable()->after('comment');
            $table->index(['product_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'ip_hash']);
            $table->dropColumn('ip_hash');
        });
    }
};
