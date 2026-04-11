<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'wholesaler', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::where('name', 'wholesaler')->where('guard_name', 'web')->delete();
    }
};
