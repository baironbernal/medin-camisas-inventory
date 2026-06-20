<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds human-readable metadata to the Spatie permissions table so the
 * admin panel can render Spanish labels and descriptions for each
 * permission. Additive and nullable — fully backward compatible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        Schema::table($table, function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'label')) {
                $table->string('label')->nullable()->after('name');
            }
            if (! Schema::hasColumn($table->getTable(), 'description')) {
                $table->string('description', 500)->nullable()->after('label');
            }
            if (! Schema::hasColumn($table->getTable(), 'module')) {
                $table->string('module')->nullable()->after('description')->index();
            }
        });
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        Schema::table($table, function (Blueprint $table) {
            $table->dropColumn(['label', 'description', 'module']);
        });
    }
};
