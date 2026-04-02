<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('wompi_transaction_id')->nullable()->after('order_number');
            $table->index('wompi_transaction_id');
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['wompi_transaction_id']);
            $table->dropColumn('wompi_transaction_id');
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
