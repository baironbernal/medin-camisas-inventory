<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $filter = 'monthly';

    protected function getStats(): array
    {
        $period = $this->filter ?? 'monthly';

        $dateFilter = match ($period) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $ordersQuery = Order::where('created_at', '>=', $dateFilter);
        $orderItemsQuery = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $dateFilter);

        $salesCount = $orderItemsQuery->sum('quantity');
        $salesRevenue = $orderItemsQuery->sum('order_items.total_price');
        $orderCount = $ordersQuery->count();

        $topSellingProducts = OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(total_price) as total_revenue')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $dateFilter)
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        $lowSellingProducts = OrderItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(total_price) as total_revenue')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $dateFilter)
            ->groupBy('product_name')
            ->orderBy('total_quantity')
            ->limit(5)
            ->get();

        $lowStockVariants = ProductVariant::whereHas('inventories', function ($query) {
            $query->where('quantity_available', '<', 15);
        })->with(['product', 'inventories.store'])->get();

        $periodLabel = match ($period) {
            'daily' => 'Hoy',
            'weekly' => 'Esta semana',
            'monthly' => 'Este mes',
            default => 'Este mes',
        };

        return [
            Stat::make('Ventas del Período', number_format($salesCount))
                ->description($periodLabel.' - Unidades vendidas')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('Ingresos del Período', '$'.number_format($salesRevenue, 2))
                ->description($periodLabel.' - Revenue')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Pedidos', number_format($orderCount))
                ->description($periodLabel.' - Total orders')
                ->icon('heroicon-o-receipt-percent'),

            Stat::make('Stock Bajo', $lowStockVariants->count())
                ->description('Variantes con <15 unidades')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'daily' => 'Hoy',
            'weekly' => 'Esta semana',
            'monthly' => 'Este mes',
        ];
    }
}
