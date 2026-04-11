<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AdvancedStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $start    = now()->startOfMonth();
        $prevStart = now()->subMonth()->startOfMonth();
        $prevEnd   = now()->subMonth()->endOfMonth();

        // ── Current period ───────────────────────────────────────────────
        $currentItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $start);

        $salesCount   = (clone $currentItems)->sum('quantity');
        $salesRevenue = (clone $currentItems)->sum('order_items.total_price');
        $orderCount   = Order::where('created_at', '>=', $start)->count();

        // ── Previous period (for progress comparison) ────────────────────
        $prevItems = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$prevStart, $prevEnd]);

        $prevSalesCount   = (clone $prevItems)->sum('quantity');
        $prevSalesRevenue = (clone $prevItems)->sum('order_items.total_price');
        $prevOrderCount   = Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $salesProgress   = $this->progressVsPrev($salesCount, $prevSalesCount);
        $revenueProgress = $this->progressVsPrev($salesRevenue, $prevSalesRevenue);
        $ordersProgress  = $this->progressVsPrev($orderCount, $prevOrderCount);

        // ── Last 7 days sparklines ────────────────────────────────────────
        $unitChart    = $this->last7DaysUnits();
        $revenueChart = $this->last7DaysRevenue();
        $ordersChart  = $this->last7DaysOrders();

        // ── Low stock ─────────────────────────────────────────────────────
        $totalVariants  = ProductVariant::count();
        $lowStockCount  = ProductVariant::whereHas('inventories', fn ($q) =>
            $q->where('quantity_available', '<', 15)
        )->count();
        $lowStockProgress = $totalVariants > 0
            ? (int) round($lowStockCount / $totalVariants * 100)
            : 0;

        return [
            Stat::make('Unidades Vendidas', number_format($salesCount))
                ->description('Este mes vs mes anterior: '.($prevSalesCount > 0 ? round($salesCount / $prevSalesCount * 100).'%' : 'N/A'))
                ->descriptionIcon($salesCount >= $prevSalesCount ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'before')
                ->descriptionColor($salesCount >= $prevSalesCount ? 'success' : 'danger')
                ->icon('heroicon-o-shopping-bag')
                ->iconColor('success')
                ->iconBackgroundColor('success')
                ->chart($unitChart)
                ->chartColor('success', 'success')
                ->progress($salesProgress)
                ->progressBarColor('success'),

            Stat::make('Ingresos del Mes', '$'.number_format($salesRevenue, 0, ',', '.'))
                ->description('Este mes vs mes anterior: '.($prevSalesRevenue > 0 ? round($salesRevenue / $prevSalesRevenue * 100).'%' : 'N/A'))
                ->descriptionIcon($salesRevenue >= $prevSalesRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'before')
                ->descriptionColor($salesRevenue >= $prevSalesRevenue ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('primary')
                ->iconBackgroundColor('primary')
                ->chart($revenueChart)
                ->chartColor('primary', 'primary')
                ->progress($revenueProgress)
                ->progressBarColor('primary'),

            Stat::make('Pedidos del Mes', number_format($orderCount))
                ->description('Este mes vs mes anterior: '.($prevOrderCount > 0 ? round($orderCount / $prevOrderCount * 100).'%' : 'N/A'))
                ->descriptionIcon($orderCount >= $prevOrderCount ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'before')
                ->descriptionColor($orderCount >= $prevOrderCount ? 'success' : 'danger')
                ->icon('heroicon-o-receipt-percent')
                ->iconColor('warning')
                ->iconBackgroundColor('warning')
                ->chart($ordersChart)
                ->chartColor('warning', 'warning')
                ->progress($ordersProgress)
                ->progressBarColor('warning'),

            Stat::make('Stock Bajo', $lowStockCount.' variantes')
                ->description($lowStockProgress.'% del total de variantes con <15 uds.')
                ->descriptionIcon('heroicon-m-exclamation-triangle', 'before')
                ->descriptionColor('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->iconBackgroundColor('danger')
                ->progress($lowStockProgress)
                ->progressBarColor('danger'),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function progressVsPrev(float|int $current, float|int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) min(100, round($current / $previous * 100));
    }

    private function last7DaysUnits(): array
    {
        return $this->last7DaysQuery(fn ($date) =>
            OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereDate('orders.created_at', $date)
                ->sum('quantity')
        );
    }

    private function last7DaysRevenue(): array
    {
        return $this->last7DaysQuery(fn ($date) =>
            OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereDate('orders.created_at', $date)
                ->sum('order_items.total_price')
        );
    }

    private function last7DaysOrders(): array
    {
        return $this->last7DaysQuery(fn ($date) =>
            Order::whereDate('created_at', $date)->count()
        );
    }

    private function last7DaysQuery(callable $resolver): array
    {
        $chart = [];

        for ($i = 6; $i >= 0; $i--) {
            $date        = now()->subDays($i)->toDateString();
            $label       = now()->subDays($i)->format('d/m');
            $chart[$label] = (float) $resolver($date);
        }

        return $chart;
    }
}
