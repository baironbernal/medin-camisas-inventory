<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Browser data captured at checkout (fbp, fbc, IP, user agent, page URL)
            // so the Meta Conversions API Purchase sent from the Wompi webhook can be
            // matched to the visitor. The webhook itself comes from Wompi, not the browser.
            $table->json('meta_tracking')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('meta_tracking');
        });
    }
};
