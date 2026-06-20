<?php

namespace Database\Seeders;

use App\Authorization\PermissionCatalog;
use App\Authorization\RolePresets;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates/updates roles and (re)assigns their permissions from RolePresets.
 *
 * Backward compatible:
 *   - Existing roles are reused (firstOrCreate) — assignments to users are
 *     preserved because we never delete the Role row.
 *   - `syncPermissions()` replaces a role's permission set with the canonical
 *     dotted permissions, completing the cutover from legacy names.
 *   - The 'wholesaler' storefront role is intentionally left untouched.
 *
 * Run order: this seeder ensures the catalog is present first, so it is safe
 * to run standalone (`--class=RolesAndPermissionsSeeder`).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure the permission catalog exists before assigning.
        $this->call(PermissionCatalogSeeder::class);

        $guard       = PermissionCatalog::GUARD;
        $allCatalog  = PermissionCatalog::names();

        foreach (RolePresets::map() as $roleName => $permissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => $guard]
            );

            $names = $permissions === RolePresets::ALL
                ? $allCatalog
                : $permissions;

            // Guard against typos in presets — only sync names that truly exist.
            $valid = Permission::where('guard_name', $guard)
                ->whereIn('name', $names)
                ->pluck('name')
                ->all();

            if ($missing = array_diff($names, $valid)) {
                $this->command?->warn(
                    "Role [{$roleName}] references unknown permissions: " . implode(', ', $missing)
                );
            }

            $role->syncPermissions($valid);

            $this->command?->info("Role [{$roleName}] synced with " . count($valid) . ' permissions.');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
