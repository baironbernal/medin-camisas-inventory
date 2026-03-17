<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();

        // Create permissions
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Store management
            'view_stores',
            'create_stores',
            'edit_stores',
            'delete_stores',
            'view_all_stores',

            // Product management
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'manage_prices',

            // Inventory management
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_all_inventory',

            // Movement management
            'view_movements',
            'create_movements',
            'edit_movements',
            'delete_movements',
            'approve_transfers',

            // Reports
            'view_reports',
            'export_reports',
            'view_financial_reports',

            // System settings
            'manage_settings',
            'manage_seasons',
            'manage_categories',
            'manage_attributes',

            //Rules
            'view_discount_rules',
            'create_discount_rules',
            'edit_discount_rules',
            'update_discount_rules',
            'delete_discount_rules',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Owner - Full access
        $owner = Role::create(['name' => 'owner']);
        $owner->givePermissionTo(Permission::all());

        // Admin - Almost full access
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Inventory Manager - Manage inventory across all stores
        $inventoryManager = Role::create(['name' => 'inventory_manager']);
        $inventoryManager->givePermissionTo([
            'view_stores',
            'view_all_stores',
            'view_products',
            'create_products',
            'edit_products',
            'manage_prices',
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_all_inventory',
            'view_movements',
            'create_movements',
            'approve_transfers',
            'view_reports',
            'export_reports',
            'manage_categories',
            'manage_attributes',
            'view_discount_rules',
            'create_discount_rules',
            'edit_discount_rules',
            'update_discount_rules',
            'delete_discount_rules',
        ]);

        // Store Supervisor - Manage single store
        $storeSupervisor = Role::create(['name' => 'store_supervisor']);
        $storeSupervisor->givePermissionTo([
            'view_stores',
            'view_products',
            'view_inventory',
            'edit_inventory',
            'adjust_inventory',
            'transfer_inventory',
            'view_movements',
            'create_movements',
            'view_reports',
        ]);

        // Warehouse Operator - Basic inventory operations
        $warehouseOperator = Role::create(['name' => 'warehouse_operator']);
        $warehouseOperator->givePermissionTo([
            'view_products',
            'view_inventory',
            'edit_inventory',
            'view_movements',
            'create_movements',
        ]);

        // Seller - Read only access
        $seller = Role::create(['name' => 'seller']);
        $seller->givePermissionTo([
            'view_products',
            'view_inventory',
        ]);
    }
}


