<?php

namespace App\Authorization;

/**
 * =============================================================================
 *  ROLE PRESETS — declarative Role -> Permission assignments.
 * =============================================================================
 *
 * Roles are PURELY permission-based. No role name is ever hardcoded inside a
 * policy; policies only check permissions. This file is the only place that
 * maps a role to the set of permissions it owns.
 *
 *  - 'owner' / 'admin' use the '*' sentinel = every permission in the catalog.
 *  - All other roles list explicit dotted permission names from PermissionCatalog.
 *
 * Repurposed per migration decision:
 *   - 'warehouse_operator' now carries the "Bodega" (Warehouse) permission set.
 *   - 'seller'             now carries the "Ventas" (Sales) permission set.
 *
 * 'wholesaler' is intentionally absent: it is a storefront customer role with
 * no admin-panel permissions and must not be granted any here.
 */
class RolePresets
{
    /** Sentinel meaning "all permissions in the catalog". */
    public const ALL = '*';

    /**
     * @return array<string, array<int, string>|string>
     */
    public static function map(): array
    {
        return [
            // ---- Full access --------------------------------------------------
            'owner' => self::ALL,
            'admin' => self::ALL,

            // ---- Inventory Manager — cross-store catalog & stock -------------
            'inventory_manager' => array_merge(
                ['dashboard.view'],
                self::module('stores', ['view_any', 'view', 'view_all']),
                self::module('products', ['view_any', 'view', 'create', 'update', 'view_prices', 'view_stock', 'manage_attributes', 'export']),
                self::module('categories', PermissionCatalog::CRUD),
                self::module('inventory', ['view_any', 'view', 'create', 'update', 'adjust', 'entries', 'exits', 'transfer', 'view_all']),
                self::module('movements', ['view_any', 'view', 'create', 'update', 'approve_transfer', 'view_all']),
                self::module('seasons', PermissionCatalog::CRUD),
                self::module('discounts', PermissionCatalog::CRUD),
                self::module('discount_rules', PermissionCatalog::CRUD),
            ),

            // ---- Store Supervisor — single assigned store -------------------
            'store_supervisor' => array_merge(
                ['dashboard.view'],
                self::module('stores', ['view_any', 'view']),
                self::module('products', ['view_any', 'view', 'view_prices', 'view_stock']),
                self::module('categories', ['view_any', 'view']),
                self::module('inventory', ['view_any', 'view', 'update', 'adjust', 'entries', 'exits', 'transfer']),
                self::module('movements', ['view_any', 'view', 'create']),
                self::module('seasons', ['view_any', 'view']),
            ),

            // ---- BODEGA (Warehouse) — repurposes warehouse_operator ---------
            'warehouse_operator' => array_merge(
                ['dashboard.view'],
                self::module('categories', ['view_any', 'view']),
                self::module('products', ['view_any', 'view', 'create', 'update']),
                self::module('orders', ['view_any', 'view', 'update', 'change_status', 'add_products', 'modify_quantities']),
                self::module('inventory', ['view_any', 'view', 'create', 'adjust', 'entries', 'exits']),
                self::module('movements', ['view_any', 'view', 'create']),
                self::module('stores', ['view_any', 'view']),
                self::module('seasons', ['view_any', 'view']),
            ),

            // ---- VENTAS (Sales) — repurposes seller -------------------------
            'seller' => array_merge(
                ['dashboard.view'],
                self::module('categories', ['view_any', 'view']),
                self::module('products', ['view_any', 'view', 'view_prices', 'view_stock']),
                self::module('orders', ['view_any', 'view', 'create', 'update', 'change_status', 'add_products', 'modify_quantities', 'register_customer']),
                self::module('inventory', ['view_any', 'view']),
                self::module('movements', ['view_any', 'view_sales_related']),
                self::module('stores', ['view_any', 'view']),
            ),
        ];
    }

    /** Build dotted permission names for a module: module('orders', ['view']) => ['orders.view']. */
    protected static function module(string $module, array $actions): array
    {
        return array_map(fn (string $action) => "{$module}.{$action}", $actions);
    }
}
