<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable()->after('phone_number');
            $table->string('city')->nullable()->after('whatsapp_number');
            $table->string('selling_channel')->nullable()->after('city')
                ->comment('Tienda física / Instagram / WhatsApp / Personal');
            $table->string('business_name')->nullable()->after('selling_channel');
            $table->string('clothing_type')->nullable()->after('business_name')
                ->comment('Hombre / Dama / Niño / Mixto');
            $table->string('selling_location')->nullable()->after('clothing_type')
                ->comment('Tienda física / Redes sociales / Catálogo / Otro');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'city',
                'selling_channel',
                'business_name',
                'clothing_type',
                'selling_location',
            ]);
        });
    }
};
