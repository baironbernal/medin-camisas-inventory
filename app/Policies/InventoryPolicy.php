<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        if ($user->can('view_all_inventory')) {
            return true;
        }

        return $user->can('view_inventory') &&
               $user->canAccessStore($inventory->store);
    }

    public function create(User $user): bool
    {
        return $user->can('edit_inventory');
    }

    public function update(User $user, Inventory $inventory): bool
    {
        if ($user->can('edit_inventory')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($inventory->store);
        }

        return false;
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->hasRole(['owner', 'admin']);
    }

    public function adjust(User $user, Inventory $inventory): bool
    {
        if ($user->can('adjust_inventory')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($inventory->store);
        }

        return false;
    }

    public function transfer(User $user, Inventory $inventory): bool
    {
        return $user->can('transfer_inventory');
    }
}


