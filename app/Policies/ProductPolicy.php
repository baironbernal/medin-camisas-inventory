<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    protected string $module = 'products';

    /** View commercial prices / costs. */
    public function viewPrices(User $user): bool
    {
        return $this->allows($user, 'view_prices');
    }

    /** View stock / availability figures. */
    public function viewStock(User $user): bool
    {
        return $this->allows($user, 'view_stock');
    }

    /** Manage attributes and variants. */
    public function manageAttributes(User $user): bool
    {
        return $this->allows($user, 'manage_attributes');
    }

    public function duplicate(User $user, Product $product): bool
    {
        return $this->allows($user, 'duplicate');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'export');
    }

    public function import(User $user): bool
    {
        return $this->allows($user, 'import');
    }
}
