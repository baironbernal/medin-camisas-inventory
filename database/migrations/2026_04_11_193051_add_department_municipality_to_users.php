<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('city')
                ->constrained('departamentos')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->after('department_id')
                ->constrained('municipios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['municipality_id']);
            $table->dropColumn(['department_id', 'municipality_id']);
        });
    }
};
