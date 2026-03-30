<?php

namespace App\Filament\Widgets;

use App\Models\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Variantes con Stock Bajo';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->whereHas('inventories', function ($query) {
                        $query->where('quantity_available', '<', 15);
                    })
                    ->with(['product', 'inventories.store'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('attributes_text')
                    ->label('Variante'),
                Tables\Columns\TextColumn::make('inventories.quantity_available')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => $state < 5 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('inventories.store.name')
                    ->label('Tienda'),
            ])
            ->heading('Variantes con Bajo Stock (<15 unidades)');
    }
}
