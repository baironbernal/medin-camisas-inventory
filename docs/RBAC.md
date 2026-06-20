# RBAC Architecture — Roles, Permissions & Policies

Enterprise authorization for the MEDIN admin panel, built on **Spatie Laravel
Permission** + Laravel Policies + Filament. This document is the reference for
how authorization works and how to extend it.

---

## 1. Design principles

1. **Single Source of Truth** — every permission is declared exactly once in
   [`app/Authorization/PermissionCatalog.php`](../app/Authorization/PermissionCatalog.php).
2. **Canonical naming** — `<module>.<action>` dotted convention
   (`products.view_any`, `orders.change_status`). The legacy `verb_noun` names
   were fully removed.
3. **Permission-based only** — policies evaluate **permissions**, never role
   names. `$user->can('products.create')` ✅ — `$user->role === 'admin'` ❌.
4. **Centralised super-grant** — `owner` and `admin` bypass all checks via a
   single `Gate::before()` in `AppServiceProvider`. No policy repeats this.
5. **Convention over configuration** — a new model needs **one** catalog entry
   plus a 3-line policy; CRUD permissions, Spanish labels/descriptions, role
   assignment and Filament gating all follow automatically.

---

## 2. Component map

| Concern | File |
|---|---|
| Permission definitions (SSOT) | `app/Authorization/PermissionCatalog.php` |
| Role → permission presets | `app/Authorization/RolePresets.php` |
| Permission model + metadata | `app/Models/Permission.php` + `…add_metadata_to_permissions_table` migration |
| Base policy (CRUD → permission) | `app/Policies/BasePolicy.php` |
| Per-model policies | `app/Policies/*Policy.php` |
| Sync permissions to DB | `database/seeders/PermissionCatalogSeeder.php` |
| Create roles + assign | `database/seeders/RolesAndPermissionsSeeder.php` |
| Policy registration + super-grant | `app/Providers/AppServiceProvider.php` |

---

## 3. Roles

| Role | Source | Description |
|---|---|---|
| `owner` | preset `*` | Full access (super-grant). |
| `admin` | preset `*` | Full access (super-grant). |
| `inventory_manager` | preset | Cross-store catalog, inventory & movements. |
| `store_supervisor` | preset | Single assigned store operations. |
| `warehouse_operator` | preset = **Bodega** | Warehouse: products (no delete), order fulfilment, inventory entries/exits/adjust. |
| `seller` | preset = **Ventas** | Sales: order creation/management, read-only inventory, sales movements. |
| `wholesaler` | runtime | Storefront customer, **no panel permissions**. Never seeded — untouched by RBAC. |

> **Repurposed:** per the migration decision, `warehouse_operator` carries the
> *Bodega* set and `seller` carries the *Ventas* set. Existing user→role
> assignments are preserved (roles are `firstOrCreate`d, only their permission
> sets are re-synced).

---

## 4. Authorization matrix (excerpt)

Legend: ● granted · `super` = via Gate::before. Full matrix is derivable from
`RolePresets::map()`.

| Permission | owner/admin | inventory_manager | store_supervisor | warehouse_operator (Bodega) | seller (Ventas) |
|---|:--:|:--:|:--:|:--:|:--:|
| `dashboard.view` | super | ● | ● | ● | ● |
| `products.view_any` | super | ● | ● | ● | ● |
| `products.create` | super | ● | | ● | |
| `products.update` | super | ● | | ● | |
| `products.delete` | super | | | | |
| `products.view_prices` | super | ● | ● | | ● |
| `products.view_stock` | super | ● | ● | | ● |
| `orders.view_any` | super | | | ● | ● |
| `orders.create` | super | | | | ● |
| `orders.change_status` | super | | | ● | ● |
| `orders.add_products` | super | | | ● | ● |
| `orders.register_customer` | super | | | | ● |
| `orders.delete` | super | | | | |
| `inventory.adjust` | super | ● | ● | ● | |
| `inventory.entries` | super | ● | ● | ● | |
| `inventory.exits` | super | ● | ● | ● | |
| `movements.create` | super | ● | ● | ● | |
| `movements.view_sales_related` | super | | | | ● |
| `stores.view_all` | super | ● | | | |
| `users.view_any` | super | | | | |

---

## 5. Permission catalog

Each permission row carries `name`, `label` (ES), `description` (ES) and
`module`. Generated from `PermissionCatalog::all()`:

```json
{ "name": "products.create", "label": "Crear Productos",
  "description": "Permite registrar nuevos Productos en el sistema.",
  "module": "products" }
```

Standard CRUD (`view_any, view, create, update, delete`) is auto-generated for
every module. Business actions are declared explicitly, e.g. `orders`:
`change_status, cancel, add_products, modify_quantities, register_customer,
assign, export`; `inventory`: `adjust, entries, exits, transfer, view_all`.

Inspect the live catalog:

```bash
php artisan tinker --execute='dump(App\Authorization\PermissionCatalog::grouped());'
```

---

## 6. Policies

All policies extend `BasePolicy`, which maps `viewAny/view/create/update/delete/
restore/forceDelete` to `<module>.<action>`. A trivial resource is 3 lines:

```php
class CategoryPolicy extends BasePolicy
{
    protected string $module = 'categories';
}
```

Business abilities add explicit methods that still only check permissions
(plus state guards, which are allowed — they are not role checks):

```php
// OrderPolicy
public function changeStatus(User $user, Order $order): bool
{
    return $this->allows($user, 'change_status');
}

public function cancel(User $user, Order $order): bool
{
    // permission + business-state guard (never cancel a delivered order)
    return $this->allows($user, 'cancel') && $order->canBeCancelled();
}
```

Custom methods implemented: **OrderPolicy** (changeStatus, cancel, addProducts,
modifyQuantities, registerCustomer, assign, export), **InventoryPolicy**
(adjust, createEntry, createExit, transfer), **MovementPolicy** (createMovement,
approveTransfer, viewSalesRelated), **ProductPolicy** (viewPrices, viewStock,
manageAttributes, duplicate, export, import), **UserPolicy** (manageRoles +
self-delete guard).

---

## 7. Seeding / deploy

```bash
php artisan migrate --force                                  # adds label/description/module cols
php artisan db:seed --class=RolesAndPermissionsSeeder --force # syncs catalog + roles (idempotent)
```

`RolesAndPermissionsSeeder` internally runs `PermissionCatalogSeeder` first, so
it is safe to run standalone. Both are **idempotent** and prune stale rows.

---

## 8. Middleware examples

```php
// routes/web.php or api.php — Spatie middleware
Route::middleware('permission:orders.create')->post('/orders', ...);
Route::middleware('role:warehouse_operator')->group(...);          // discouraged; prefer permission
Route::middleware('role_or_permission:admin|orders.export')->get('/orders/export', ...);

// Laravel native gate middleware on a model route
Route::put('/orders/{order}', ...)->middleware('can:update,order');
```

Controller / blade:

```php
$this->authorize('changeStatus', $order);     // controller
@can('products.view_prices') ... @endcan      // blade
```

---

## 9. Filament integration

Filament v3 **auto-consumes model policies** — no extra wiring needed for the
common cases:

| Filament behaviour | Backed by policy method |
|---|---|
| Navigation item visible | `viewAny()` |
| List page access | `viewAny()` |
| View page / row | `view()` |
| Create button/page | `create()` |
| Edit button/page | `update()` |
| Delete / bulk delete | `delete()` |

Custom actions are gated explicitly with the policy ability:

```php
Tables\Actions\Action::make('confirm_order')
    ->visible(fn (Order $r) => auth()->user()->can('changeStatus', $r));

Actions\Action::make('cancel_order')
    ->visible(fn (Order $r) => auth()->user()->can('cancel', $r));
```

Resource-level override (e.g. UserResource):

```php
public static function canAccess(): bool
{
    return auth()->user()?->can('users.view_any') ?? false;
}
```

> **Note for sales movements:** `seller` holds `movements.view_sales_related`
> (not `view_any`). `MovementPolicy::viewAny()` accepts either so the nav item
> shows; scope the `MovementResource` Eloquent query to sales-related rows for
> that permission to honour the restriction.

---

## 10. Future expansion guide

### Add a new model (e.g. `Supplier`)

1. **Catalog** — one entry in `PermissionCatalog::modules()`:
   ```php
   'suppliers' => [
       'label' => 'Proveedores', 'gender' => 'm',
       'actions' => [...self::CRUD, 'export'],
       'extra' => [
           'approve' => ['label' => 'Aprobar Proveedores',
                         'description' => 'Permite aprobar proveedores.'],
       ],
   ],
   ```
2. **Policy** — `app/Policies/SupplierPolicy.php`:
   ```php
   class SupplierPolicy extends BasePolicy {
       protected string $module = 'suppliers';
       public function approve(User $u, Supplier $s): bool { return $this->allows($u, 'approve'); }
   }
   ```
3. **Register** — add `Supplier::class => SupplierPolicy::class` to
   `AppServiceProvider::$policies` (or rely on auto-discovery).
4. **Roles** — reference `suppliers.*` names in `RolePresets::map()` for the
   roles that should get them.
5. **Seed** — `php artisan db:seed --class=RolesAndPermissionsSeeder --force`.

CRUD permissions, Spanish labels/descriptions, Filament gating and the matrix
update automatically.

### Add a new permission to an existing model
Add the action to that module's `actions`/`extra` in the catalog, then re-seed.

### Add a new role
Add a key to `RolePresets::map()` with its dotted permission list (use the
`module()` helper), then re-seed. Existing user assignments are never dropped.

### Conventions to keep
- Never write `$user->hasRole(...)` inside a policy.
- Never create a permission outside the catalog.
- Keep `owner`/`admin` out of presets' explicit lists — they use `*`.
