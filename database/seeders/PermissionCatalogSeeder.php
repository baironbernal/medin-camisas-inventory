<?php

namespace Database\Seeders;

use App\Authorization\PermissionCatalog;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Synchronises the `permissions` table with the PermissionCatalog (the single
 * source of truth).
 *
 *   - Creates permissions that don't exist.
 *   - Updates label/description/module on existing ones (no duplicates: keyed
 *     by unique name + guard).
 *   - Prunes permissions that are no longer in the catalog (full cutover),
 *     which removes the legacy verb_noun permissions.
 *
 * Idempotent — safe to run repeatedly. This seeder NEVER touches role
 * assignments; that is RolesAndPermissionsSeeder's job.
 */
class PermissionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $guard   = PermissionCatalog::GUARD;
        $catalog = PermissionCatalog::all();

        DB::transaction(function () use ($catalog, $guard) {
            // 1. Upsert every permission from the catalog (idempotent by name+guard).
            foreach ($catalog as $perm) {
                Permission::updateOrCreate(
                    ['name' => $perm['name'], 'guard_name' => $guard],
                    [
                        'label'       => $perm['label'],
                        'description' => $perm['description'],
                        'module'      => $perm['module'],
                    ]
                );
            }

            // 2. Prune anything not in the catalog (legacy verb_noun cutover).
            $canonical = array_column($catalog, 'name');
            $stale     = Permission::where('guard_name', $guard)
                ->whereNotIn('name', $canonical)
                ->get();

            foreach ($stale as $permission) {
                $permission->delete();
            }

            $this->command?->info(sprintf(
                'Permissions synced: %d in catalog, %d legacy pruned.',
                count($canonical),
                $stale->count()
            ));
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
