<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;

class MovementPolicy extends BasePolicy
{
    protected string $module = 'movements';

    /**
     * Listing is allowed for full viewers and for sales-only viewers
     * (the resource query is responsible for scoping the rows shown).
     */
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'view_any')
            || $this->allows($user, 'view_sales_related');
    }

    public function view(User $user, $movement): bool
    {
        if ($this->allows($user, 'view_all')) {
            return true;
        }

        return $this->allows($user, 'view') && $user->canAccessStore($movement->store);
    }

    /**
     * Movements may only be edited within 24h of creation, by their author
     * or a cross-store manager — gated first by the permission.
     */
    public function update(User $user, $movement): bool
    {
        if (! $this->allows($user, 'update')) {
            return false;
        }

        if ($movement->created_at->diffInHours(now()) > 24) {
            return false;
        }

        return $user->id === $movement->user_id || $this->allows($user, 'view_all');
    }

    /** Create a stock movement. */
    public function createMovement(User $user): bool
    {
        return $this->allows($user, 'create');
    }

    /** Approve a transfer between stores. */
    public function approveTransfer(User $user, Movement $movement): bool
    {
        return $movement->type === 'transfer' && $this->allows($user, 'approve_transfer');
    }

    /** See only sales-related movements. */
    public function viewSalesRelated(User $user): bool
    {
        return $this->allows($user, 'view_sales_related')
            || $this->allows($user, 'view_any');
    }
}
