<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy extends BasePolicy
{
    protected string $module = 'inventory';

    public function view(User $user, $inventory): bool
    {
        if ($this->allows($user, 'view_all')) {
            return true;
        }

        return $this->allows($user, 'view') && $user->canAccessStore($inventory->store);
    }

    public function update(User $user, $inventory): bool
    {
        return $this->allows($user, 'update') && $this->scopedToStore($user, $inventory);
    }

    /** Stock adjustments / corrections. */
    public function adjust(User $user, Inventory $inventory): bool
    {
        return $this->allows($user, 'adjust') && $this->scopedToStore($user, $inventory);
    }

    /** Register an inbound stock entry. */
    public function createEntry(User $user, ?Inventory $inventory = null): bool
    {
        return $this->allows($user, 'entries') && (! $inventory || $this->scopedToStore($user, $inventory));
    }

    /** Register an outbound stock exit. */
    public function createExit(User $user, ?Inventory $inventory = null): bool
    {
        return $this->allows($user, 'exits') && (! $inventory || $this->scopedToStore($user, $inventory));
    }

    public function transfer(User $user, ?Inventory $inventory = null): bool
    {
        return $this->allows($user, 'transfer');
    }

    /** Cross-store access OR the record belongs to the user's assigned store. */
    protected function scopedToStore(User $user, Inventory $inventory): bool
    {
        return $this->allows($user, 'view_all') || $user->canAccessStore($inventory->store);
    }
}
