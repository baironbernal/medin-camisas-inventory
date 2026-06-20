<?php

namespace App\Authorization;

/**
 * =============================================================================
 *  PERMISSION CATALOG — Single Source of Truth for the whole RBAC system.
 * =============================================================================
 *
 * Every permission in the application is defined here, exactly once, using the
 * canonical dotted convention `<module>.<action>` (e.g. products.view_any).
 *
 * The catalog is consumed by:
 *   - PermissionCatalogSeeder  -> syncs the `permissions` table (create/update/prune)
 *   - RolePresets              -> references these names when granting roles
 *   - Policies (BasePolicy)    -> resolve `<module>.<action>` from the model
 *   - Admin panel              -> reads label/description for display
 *
 * ----------------------------------------------------------------------------
 *  HOW TO ADD A NEW MODEL (e.g. Supplier, Brand, Invoice)
 * ----------------------------------------------------------------------------
 *  1. Add ONE entry to self::modules() with a Spanish `label` (plural noun)
 *     and its grammatical `gender` ('m'|'f') so labels read naturally.
 *  2. (Optional) declare `extra` business actions with explicit metadata.
 *  3. Run `php artisan db:seed --class=PermissionCatalogSeeder`.
 *
 *  The standard CRUD permissions, Spanish labels, descriptions, a Policy
 *  (via BasePolicy) and role assignment all follow automatically. No other
 *  file in the architecture needs to change.
 * ----------------------------------------------------------------------------
 */
class PermissionCatalog
{
    /** Guard the permissions are created under (Filament + web use 'web'). */
    public const GUARD = 'web';

    /** The five CRUD actions every resource module receives automatically. */
    public const CRUD = ['view_any', 'view', 'create', 'update', 'delete'];

    /**
     * Spanish templates for the standard actions. `:label` is replaced by the
     * module's plural noun, `:gender_*` resolves articles by grammatical gender.
     */
    protected const ACTION_TEMPLATES = [
        'view_any'     => ['label' => 'Ver listado de :label',         'desc' => 'Permite ver el listado completo de :label.'],
        'view'         => ['label' => 'Ver detalle de :label',         'desc' => 'Permite ver el detalle de un registro de :label.'],
        'create'       => ['label' => 'Crear :label',                  'desc' => 'Permite registrar nuevos :label en el sistema.'],
        'update'       => ['label' => 'Editar :label',                 'desc' => 'Permite modificar :label existentes.'],
        'delete'       => ['label' => 'Eliminar :label',               'desc' => 'Permite eliminar :label del sistema.'],
        'restore'      => ['label' => 'Restaurar :label',              'desc' => 'Permite restaurar :label eliminados.'],
        'force_delete' => ['label' => 'Eliminar permanentemente :label','desc' => 'Permite eliminar de forma permanente :label.'],
        'export'       => ['label' => 'Exportar :label',               'desc' => 'Permite exportar :label a archivos externos.'],
        'import'       => ['label' => 'Importar :label',               'desc' => 'Permite importar :label desde archivos externos.'],
    ];

    /**
     * Module definitions. Each key is the dotted module prefix.
     *
     *   label   => Spanish plural noun used to build labels.
     *   gender  => 'm'|'f' for article agreement (los/las).
     *   crud    => false to skip the automatic CRUD set (e.g. dashboard).
     *   actions => standard action keys to include from self::CRUD plus the
     *              generic templated ones (restore, force_delete, export...).
     *   extra   => fully custom business permissions with explicit metadata.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function modules(): array
    {
        return [
            'dashboard' => [
                'label'   => 'el Tablero',
                'gender'  => 'm',
                'crud'    => false,
                'extra'   => [
                    'view' => ['label' => 'Ver Tablero', 'description' => 'Permite acceder al tablero principal con métricas y resúmenes.'],
                ],
            ],

            'users' => [
                'label'   => 'Usuarios',
                'gender'  => 'm',
                'actions' => self::CRUD,
                'extra'   => [
                    'manage_roles' => ['label' => 'Gestionar Roles de Usuario', 'description' => 'Permite asignar o quitar roles y permisos a los usuarios.'],
                ],
            ],

            'stores' => [
                'label'   => 'Tiendas',
                'gender'  => 'f',
                'actions' => self::CRUD,
                'extra'   => [
                    'view_all' => ['label' => 'Ver Todas las Tiendas', 'description' => 'Otorga acceso a todas las tiendas, sin limitarse a la tienda asignada.'],
                ],
            ],

            'products' => [
                'label'   => 'Productos',
                'gender'  => 'm',
                'actions' => [...self::CRUD, 'restore', 'force_delete', 'export', 'import'],
                'extra'   => [
                    'view_prices'       => ['label' => 'Ver Precios de Productos', 'description' => 'Permite ver los precios y costos de los productos.'],
                    'view_stock'        => ['label' => 'Ver Stock de Productos', 'description' => 'Permite ver la disponibilidad de inventario de los productos.'],
                    'manage_attributes' => ['label' => 'Gestionar Atributos', 'description' => 'Permite administrar atributos y variantes de los productos.'],
                    'duplicate'         => ['label' => 'Duplicar Productos', 'description' => 'Permite duplicar un producto existente como base para uno nuevo.'],
                ],
            ],

            'categories' => [
                'label'   => 'Categorías',
                'gender'  => 'f',
                'actions' => self::CRUD,
            ],

            'inventory' => [
                'label'   => 'Inventario',
                'gender'  => 'm',
                'actions' => self::CRUD,
                'extra'   => [
                    'view_all' => ['label' => 'Ver Todo el Inventario', 'description' => 'Permite ver el inventario de todas las tiendas.'],
                    'adjust'   => ['label' => 'Ajustar Inventario', 'description' => 'Permite realizar ajustes de stock (correcciones de inventario).'],
                    'entries'  => ['label' => 'Registrar Entradas de Inventario', 'description' => 'Permite registrar entradas de mercancía al inventario.'],
                    'exits'    => ['label' => 'Registrar Salidas de Inventario', 'description' => 'Permite registrar salidas de mercancía del inventario.'],
                    'transfer' => ['label' => 'Transferir Inventario', 'description' => 'Permite transferir stock entre tiendas.'],
                ],
            ],

            'movements' => [
                'label'   => 'Movimientos',
                'gender'  => 'm',
                'actions' => self::CRUD,
                'extra'   => [
                    'view_all'           => ['label' => 'Ver Todos los Movimientos', 'description' => 'Permite ver los movimientos de todas las tiendas.'],
                    'approve_transfer'   => ['label' => 'Aprobar Transferencias', 'description' => 'Permite aprobar transferencias de inventario entre tiendas.'],
                    'view_sales_related' => ['label' => 'Ver Movimientos de Ventas', 'description' => 'Permite ver únicamente los movimientos relacionados con ventas.'],
                ],
            ],

            'seasons' => [
                'label'   => 'Temporadas',
                'gender'  => 'f',
                'actions' => self::CRUD,
            ],

            'orders' => [
                'label'   => 'Pedidos',
                'gender'  => 'm',
                'actions' => [...self::CRUD, 'export'],
                'extra'   => [
                    'change_status'     => ['label' => 'Cambiar Estado de Pedidos', 'description' => 'Permite cambiar el estado de los pedidos (confirmar, procesar, completar).'],
                    'cancel'            => ['label' => 'Cancelar Pedidos', 'description' => 'Permite cancelar pedidos.'],
                    'add_products'      => ['label' => 'Agregar Productos a Pedidos', 'description' => 'Permite añadir ítems a un pedido existente.'],
                    'modify_quantities' => ['label' => 'Modificar Cantidades de Pedidos', 'description' => 'Permite modificar las cantidades de los ítems de un pedido.'],
                    'register_customer' => ['label' => 'Registrar Cliente en Pedidos', 'description' => 'Permite registrar o asociar el cliente de un pedido.'],
                    'assign'            => ['label' => 'Asignar Pedidos', 'description' => 'Permite asignar un pedido a un mayorista o responsable.'],
                ],
            ],

            'discounts' => [
                'label'   => 'Descuentos',
                'gender'  => 'm',
                'actions' => self::CRUD,
            ],

            'discount_rules' => [
                'label'   => 'Reglas de Descuento',
                'gender'  => 'f',
                'actions' => self::CRUD,
            ],

            'home_ads' => [
                'label'   => 'Anuncios de Inicio',
                'gender'  => 'm',
                'actions' => self::CRUD,
            ],
        ];
    }

    /**
     * Flattened catalog: every permission with its metadata.
     *
     * @return array<int, array{name:string,label:string,description:string,module:string,action:string}>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (static::modules() as $module => $def) {
            $label = $def['label'];

            // Standard / generic templated actions
            foreach ($def['actions'] ?? [] as $action) {
                $tpl = self::ACTION_TEMPLATES[$action] ?? null;
                $permissions[] = [
                    'name'        => "{$module}.{$action}",
                    'label'       => $tpl ? str_replace(':label', $label, $tpl['label']) : ucfirst($action) . " {$label}",
                    'description' => $tpl ? str_replace(':label', $label, $tpl['desc']) : '',
                    'module'      => $module,
                    'action'      => $action,
                ];
            }

            // Fully custom business actions
            foreach ($def['extra'] ?? [] as $action => $meta) {
                $permissions[] = [
                    'name'        => "{$module}.{$action}",
                    'label'       => $meta['label'],
                    'description' => $meta['description'],
                    'module'      => $module,
                    'action'      => $action,
                ];
            }
        }

        return $permissions;
    }

    /** Flat list of every canonical permission name. */
    public static function names(): array
    {
        return array_column(static::all(), 'name');
    }

    /** Grouped by module — handy for building permission-matrix UIs. */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (static::all() as $perm) {
            $grouped[$perm['module']][] = $perm;
        }
        return $grouped;
    }

    /** Module keys, in declaration order. */
    public static function moduleKeys(): array
    {
        return array_keys(static::modules());
    }

    /**
     * Human module labels for grouping UIs (admin panel), keyed by module.
     * Strips the leading Spanish article so headings read cleanly.
     */
    public static function moduleLabels(): array
    {
        $labels = [];
        foreach (static::modules() as $key => $def) {
            $labels[$key] = preg_replace('/^(el|la|los|las)\s+/i', '', $def['label']);
        }
        return $labels;
    }
}
