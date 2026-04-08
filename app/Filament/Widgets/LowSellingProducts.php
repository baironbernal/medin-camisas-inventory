<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowSellingProducts extends BaseWidget
{
    protected int|string|array $columnSpan = 'half';

    protected function getTableHeading(): string
    {
        return 'Top 5 Productos Menos Vendidos';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()->select('product_name')
                    ->selectRaw('MAX(order_items.id) as id, SUM(quantity) as total_quantity, SUM(total_price) as total_revenue')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->groupBy('product_name')
                    ->orderBy('total_quantity')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Producto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Unidades')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Ingresos')
                    ->money('COP')
                    ->sortable(),
            ]);
    }
}
