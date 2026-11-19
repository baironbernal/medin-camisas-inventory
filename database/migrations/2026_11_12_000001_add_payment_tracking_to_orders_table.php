<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Where the order came from: web (Wompi checkout), whatsapp (web → WhatsApp), admin (panel wizard)
            $table->string('source', 20)->default('web')->after('status');
            // wompi | transferencia | contraentrega | efectivo — null until agreed (WhatsApp orders)
            $table->string('payment_method', 20)->nullable()->after('source');
            // Set when the money is actually received — an order can exist without being paid
            $table->timestamp('paid_at')->nullable()->after('payment_method');
            // Meta Conversions API idempotency: the Purchase is reported only once per order
            $table->timestamp('meta_purchase_sent_at')->nullable()->after('meta_tracking');
        });

        // Existing orders: those with a Wompi transaction were paid online; admin wizard orders
        // always carry a payment proof. Mark confirmed/completed ones as already paid and
        // already reported so the new tracking doesn't resend old sales.
        DB::table('orders')->whereNotNull('wompi_transaction_id')->update(['payment_method' => 'wompi']);
        DB::table('orders')->whereIn('status', ['confirmed', 'processing', 'completed'])->update([
            'paid_at'               => DB::raw('updated_at'),
            'meta_purchase_sent_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'payment_method', 'paid_at', 'meta_purchase_sent_at']);
        });
    }
};
