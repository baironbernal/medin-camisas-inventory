<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\SalesRevenueService;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AdvancedStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -3;

    // Statuses that count as a "confirmed sale"
    private const SALE_STATUSES = ['confirmed', 'processing', 'completed'];

    protected function getFilters(): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date           = now()->subMonths($i)->startOfMonth();
            $months[$date->format('Y-m')] = ucfirst($date->locale('es')->translatedFormat('F Y'));
        }
        return $months;
    }

    protected function getStats(): array
    {
        // ── Selected month ────────────────────────────────────────────────
        $monthKey = $this->filter ?? now()->format('Y-m');
        $start    = Carbon::createFromFormat('Y-m', $monthKey)->startOfMonth();
        $end      = Carbon::createFromFormat('Y-m', $monthKey)->endOfMonth();

        $prevStart = (clone $start)->subMonth()->startOfMonth();
        $prevEnd   = (clone $start)->subMonth()->endOfMonth();

        $statuses = self::SALE_STATUSES;

        // ── Current period ────────────────────────────────────────────────
        $currentBase = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->whereIn('orders.status', $statuses);

        $salesCount   = (clone $currentBase)->sum('quantity');
        $salesRevenue = SalesRevenueService::forPeriod($start, $end, $statuses);
        $orderCount   = Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', $statuses)
            ->count();

        // ── Previous period ────────────────────────────────────────────────
        $prevBase = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$prevStart, $prevEnd])
            ->whereIn('orders.status', $statuses);

        $prevSalesCount   = (clone $prevBase)->sum('quantity');
        $prevSalesRevenue = SalesRevenueService::forPeriod($prevStart, $prevEnd, $statuses);
        $prevOrderCount   = Order::whereBetween('created_at', [$prevStart, $prevEnd])
            ->whereIn('status', $statuses)
            ->count();

        $salesProgress   = $this->progressVsPrev($salesCount, $prevSalesCount);
        $revenueProgress = $this->progressVsPrev($salesRevenue, $prevSalesRevenue);
        $ordersProgress  = $this->progressVsPrev($orderCount, $prevOrderCount);

        // ── Sparklines — last 7 days of the selected month ─────────────────
        $unitChart    = $this->last7DaysUnits($start, $end);
        $revenueChart = $this->last7DaysRevenue($start, $end);
        $ordersChart  = $this->last7DaysOrders($start, $end);

        // ── Low stock ──────────────────────────────────────────────────────
        $totalVariants = ProductVariant::count();
        $lowStockCount = ProductVariant::whereHas('inventories', fn ($q) =>
            $q->where('quantity_available', '<', 15)
        )->count();
        $lowStockProgress = $totalVariants > 0
            ? (int) round($lowStockCount / $totalVariants * 100)
            : 0;

        $monthLabel = ucfirst(Carbon::createFromFormat('Y-m', $monthKey)->locale('es')->translatedFormat('F Y'));

        return [
            Stat::make("Unidades Vendidas — {$monthLabel}", number_format($salesCount))
                ->description('vs mes anterior: '.($prevSalesCount > 0 ? round($salesCount / $prevSalesCount * 100).'%' : 'N/A'))
                ->descriptionIcon($salesCount >= $prevSalesCount ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'before')
                ->descriptionColor($salesCount >= $prevSalesCount ? 'success' : 'danger')
                ->icon('heroicon-o-shopping-bag')
                ->iconColor('success')
                ->iconBackgroundColor('success')
                ->chart($unitChart)
                ->chartColor('success', 'success')
                ->progress($salesProgress)
                ->progressBarColor('success'),

            Stat::make("Ingresos — {$monthLabel}", '$'.number_format($salesRevenue, 0, ',', '.'))
                ->description('vs mes anterior: '.($prevSalesRevenue > 0 ? round($salesRevenue / $prevSalesRevenue * 100).'%' : 'N/A'))
                ->descriptionIcon($salesRevenue >= $prevSalesRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down', 'before')
                ->descriptionColor($salesRevenue >= $prevSalesRevenue ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar')
                ->iconColor('primary')
                ->iconBackgroundColor('primary')
                ->chart($revenueChart)
                ->chartColor('primary', 'primary')
                ->progress($revenueProgress)
                ->progressBarColor('primary'),

            Stat::make("Pedidos Confirmados — {$monthLabel}", number_format($orderCount))
                ->description('vs mes anterior: '.($prevOrderCount > 0 ? round($orderCount / $prevOrderCount * 100).'%' : 'N/A'))
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function progressVsPrev(float|int $current, float|int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }
        return (int) min(100, round($current / $previous * 100));
    }

    /** Last 7 days within the given month window (or last 7 calendar days if window includes today). */
    private function last7DaysUnits(Carbon $start, Carbon $end): array
    {
        return $this->last7DaysQuery($start, $end, fn ($date) =>
            OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereDate('orders.created_at', $date)
                ->whereIn('orders.status', self::SALE_STATUSES)
                ->sum('quantity')
        );
    }

    private function last7DaysRevenue(Carbon $start, Carbon $end): array
    {
        return $this->last7DaysQuery($start, $end, fn ($date) =>
            SalesRevenueService::forDate($date, self::SALE_STATUSES)
        );
    }

    private function last7DaysOrders(Carbon $start, Carbon $end): array
    {
        return $this->last7DaysQuery($start, $end, fn ($date) =>
            Order::whereDate('created_at', $date)
                ->whereIn('status', self::SALE_STATUSES)
                ->count()
        );
    }

    private function last7DaysQuery(Carbon $start, Carbon $end, callable $resolver): array
    {
        // Anchor point: min(end of selected month, today)
        $anchor = $end->gt(now()) ? now() : $end;

        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day   = (clone $anchor)->subDays($i);
            if ($day->lt($start)) {
                $chart[$day->format('d/m')] = 0;
                continue;
            }
            $chart[$day->format('d/m')] = (float) $resolver($day->toDateString());
        }
        return $chart;
    }
}
