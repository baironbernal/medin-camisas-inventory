<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_stores');
    }

    public function view(User $user, Store $store): bool
    {
        if ($user->can('view_all_stores')) {
            return true;
        }

        return $user->can('view_stores') &&
               $user->canAccessStore($store);
    }

    public function create(User $user): bool
    {
        return $user->can('create_stores');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->can('edit_stores');
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->can('delete_stores');
    }
}


