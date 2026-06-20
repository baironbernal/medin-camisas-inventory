<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy extends BasePolicy
{
    protected string $module = 'stores';

    /**
     * A user can view a store if they have cross-store access
     * (stores.view_all) or it is the store assigned to them.
     */
    public function view(User $user, $store): bool
    {
        if ($this->allows($user, 'view_all')) {
            return true;
        }

        return $this->allows($user, 'view') && $user->canAccessStore($store);
    }
}
