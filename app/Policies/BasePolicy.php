<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every resource policy.
 *
 * Maps the standard authorization abilities to canonical dotted permissions
 * (`<module>.<action>`) defined in {@see \App\Authorization\PermissionCatalog}.
 *
 * RULES (enforced by convention):
 *   - Policies evaluate PERMISSIONS ONLY. Never `$user->hasRole(...)`.
 *   - A concrete policy only needs to declare `$module` and, optionally,
 *     extra methods for business abilities (e.g. changeStatus, adjustStock).
 *
 * The owner/admin super-grant is handled centrally by `Gate::before()` in
 * AppServiceProvider, so it is not repeated in any policy.
 */
abstract class BasePolicy
{
    use HandlesAuthorization;

    /** Dotted module prefix, e.g. 'products'. Set by each concrete policy. */
    protected string $module;

    /** Resolve `<module>.<action>` against the user's permissions. */
    protected function allows(User $user, string $action): bool
    {
        return $user->can("{$this->module}.{$action}");
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view_any');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allows($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allows($user, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->allows($user, 'delete');
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->allows($user, 'restore');
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->allows($user, 'force_delete');
    }
}
