<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name');
            $table->string('last_name')->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId('assigned_store_id')->nullable()->constrained('stores');
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['assigned_store_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone_number',
                'is_active',
                'assigned_store_id',
                'last_login_at'
            ]);
        });
    }
};


