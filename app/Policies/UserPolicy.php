<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    protected string $module = 'users';

    /**
     * A user may never delete their own account, even with the permission.
     */
    public function delete(User $user, $model): bool
    {
        return $this->allows($user, 'delete') && $user->id !== $model->id;
    }

    /** Assign/revoke roles and permissions to other users. */
    public function manageRoles(User $user): bool
    {
        return $this->allows($user, 'manage_roles');
    }
}
