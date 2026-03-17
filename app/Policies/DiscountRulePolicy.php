<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DiscountRule;

class DiscountRulePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_discount_rules');
    }

    public function view(User $user, DiscountRule $discountRule): bool
    {
        return $user->can('view_discount_rules');
    }

    public function create(User $user): bool
    {
        return $user->can('create_discount_rules');
    }

    public function update(User $user, DiscountRule $discountRule): bool
    {
        return $user->can('update_discount_rules');
    }

    public function delete(User $user, DiscountRule $discountRule): bool
    {
        return $user->can('delete_discount_rules');
    }
}
