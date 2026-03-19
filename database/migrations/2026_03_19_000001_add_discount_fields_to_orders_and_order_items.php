<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_original', 12, 2)->default(0)->after('subtotal');
            $table->decimal('subtotal_discounted', 12, 2)->default(0)->after('subtotal_original');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('discount_rule_id')
                ->nullable()
                ->constrained('discount_rules')
                ->nullOnDelete()
                ->after('variant_sku');

            $table->decimal('discount_percentage', 6, 2)->default(0)->after('discount_rule_id');
            $table->decimal('discounted_unit_price', 12, 2)->default(0)->after('unit_price');
            $table->decimal('discounted_total_price', 12, 2)->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_rule_id');
            $table->dropColumn('discount_percentage');
            $table->dropColumn('discounted_unit_price');
            $table->dropColumn('discounted_total_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('subtotal_original');
            $table->dropColumn('subtotal_discounted');
        });
    }
};

