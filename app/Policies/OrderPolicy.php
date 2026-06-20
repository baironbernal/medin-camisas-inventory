<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    protected string $module = 'orders';

    /**
     * Orders that have been completed (invoiced/delivered) are locked from
     * further edits regardless of permission — a business-state guard, not a
     * role check.
     */
    public function update(User $user, $order): bool
    {
        if (! $this->allows($user, 'update')) {
            return false;
        }

        return ! in_array($order->status, [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED], true);
    }

    public function delete(User $user, $order): bool
    {
        // Only pending orders may be hard-deleted, and only with permission.
        return $this->allows($user, 'delete') && $order->status === Order::STATUS_PENDING;
    }

    /** Move an order through its lifecycle (confirm, process, complete). */
    public function changeStatus(User $user, Order $order): bool
    {
        return $this->allows($user, 'change_status');
    }

    /** Cancel an order. A delivered/completed order can never be cancelled. */
    public function cancel(User $user, Order $order): bool
    {
        return $this->allows($user, 'cancel') && $order->canBeCancelled();
    }

    /** Add line items to an existing order. */
    public function addProducts(User $user, Order $order): bool
    {
        return $this->allows($user, 'add_products') && $order->status === Order::STATUS_PENDING;
    }

    /** Modify quantities of existing line items. */
    public function modifyQuantities(User $user, Order $order): bool
    {
        return $this->allows($user, 'modify_quantities') && $order->status === Order::STATUS_PENDING;
    }

    /** Register / associate the customer on an order. */
    public function registerCustomer(User $user, ?Order $order = null): bool
    {
        return $this->allows($user, 'register_customer');
    }

    /** Assign an order to a wholesaler / responsible user. */
    public function assign(User $user, Order $order): bool
    {
        return $this->allows($user, 'assign');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'export');
    }
}
