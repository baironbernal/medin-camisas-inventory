<?php

namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Support\Carbon;

class SalesRevenueService
{
    /**
     * Total revenue for a period with all discounts applied.
     *
     * discounted_total_price already reflects:
     *   - Volume/cart discount (discount_rule_id + discount_percentage)
     *   - Manual discount applied in Filament (discount_id)
     *
     * So summing this column gives the real money received, not the gross catalog price.
     */
    /** @param string[] $statuses  If empty, all statuses are included. */
    public static function forPeriod(Carbon $from, ?Carbon $to = null, array $statuses = []): float
    {
        $query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $from);

        if ($to) {
            $query->where('orders.created_at', '<=', $to);
        }

        if (! empty($statuses)) {
            $query->whereIn('orders.status', $statuses);
        }

        return (float) $query->sum('order_items.discounted_total_price');
    }

    /** @param string[] $statuses  If empty, all statuses are included. */
    public static function forDate(string $date, array $statuses = []): float
    {
        $query = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $date);

        if (! empty($statuses)) {
            $query->whereIn('orders.status', $statuses);
        }

        return (float) $query->sum('order_items.discounted_total_price');
    }
}
