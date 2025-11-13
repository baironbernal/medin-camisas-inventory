<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;

class MovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_movements');
    }

    public function view(User $user, Movement $movement): bool
    {
        if ($user->can('view_movements')) {
            if ($user->hasRole(['owner', 'admin', 'inventory_manager'])) {
                return true;
            }

            return $user->canAccessStore($movement->store);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_movements');
    }

    public function update(User $user, Movement $movement): bool
    {
        // Only allow editing recent movements (within 24 hours)
        if ($movement->created_at->diffInHours(now()) > 24) {
            return false;
        }

        return $user->can('edit_movements') &&
               ($user->id === $movement->user_id ||
                $user->hasRole(['owner', 'admin', 'inventory_manager']));
    }

    public function delete(User $user, Movement $movement): bool
    {
        return $user->can('delete_movements') &&
               $user->hasRole(['owner', 'admin']);
    }

    public function approveTransfer(User $user, Movement $movement): bool
    {
        return $movement->type === 'transfer' &&
               $user->can('approve_transfers');
    }
}


