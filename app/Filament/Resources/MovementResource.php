<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MovementResource\Pages;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MovementResource extends Resource
{
    protected static ?string $model = Movement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Movimientos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Movimiento')
                    ->description('Seleccione el tipo de movimiento, producto y tienda')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Movimiento')
                            ->options([
                                'purchase' => '📦 Compra (Entrada)',
                                'sale' => '💰 Venta (Salida)',
                                'transfer' => '🔄 Transferencia',
                                'adjustment' => '⚖️ Ajuste de Inventario',
                                'return' => '↩️ Devolución (Entrada)',
                                'damage' => '❌ Daño/Pérdida (Salida)',
                            ])
                            ->required()
                            ->live()
                            ->helperText(fn ($state) => match($state) {
                                'purchase' => 'Entrada de mercancía comprada al proveedor',
                                'sale' => 'Salida por venta a cliente',
                                'transfer' => 'Movimiento entre tiendas',
                                'adjustment' => 'Corrección de inventario (conteo físico)',
                                'return' => 'Devolución de cliente (entrada)',
                                'damage' => 'Producto dañado o perdido (salida)',
                                default => 'Seleccione un tipo de movimiento'
                            }),
                        Forms\Components\Select::make('product_variant_id')
                            ->label('Variante de Producto')
                            ->options(function () {
                                return ProductVariant::with('product')
                                    ->get()
                                    ->mapWithKeys(function ($variant) {
                                        $attributes = $variant->variantAttributes()
                                            ->with(['attribute', 'attributeValue'])
                                            ->get()
                                            ->map(function ($va) {
                                                return $va->attributeValue->value ?? '';
                                            })
                                            ->filter()
                                            ->join(' / ');
                                        
                                        $label = $variant->sku . ' - ' . $variant->product->name;
                                        if ($attributes) {
                                            $label .= ' (' . $attributes . ')';
                                        }
                                        
                                        return [$variant->id => $label];
                                    });
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                // Show current stock when variant and store are selected
                                $storeId = $get('store_id');
                                if ($state && $storeId) {
                                    $inventory = Inventory::where('product_variant_id', $state)
                                        ->where('store_id', $storeId)
                                        ->first();
                                    
                                    if ($inventory) {
                                        $set('_current_stock', $inventory->quantity_available);
                                        $set('_reserved_stock', $inventory->quantity_reserved);
                                        $set('_inventory_id', $inventory->id);
                                    } else {
                                        $set('_current_stock', 0);
                                        $set('_reserved_stock', 0);
                                        $set('_inventory_id', null);
                                    }
                                }
                                // Reset store when product changes
                                $set('store_id', null);
                            }),
                        Forms\Components\Select::make('store_id')
                            ->label('Tienda')
                            ->options(function (Forms\Get $get) {
                                $variantId = $get('product_variant_id');
                                $type = $get('type');
                                
                                // For sales and damage, only show stores with available inventory
                                if (in_array($type, ['sale', 'damage', 'transfer']) && $variantId) {
                                    return \App\Models\Store::whereHas('inventories', function ($query) use ($variantId) {
                                        $query->where('product_variant_id', $variantId)
                                              ->where('quantity_available', '>', 0);
                                    })->pluck('name', 'id');
                                }
                                
                                // For purchases, returns, and adjustments, show all active stores
                                return \App\Models\Store::where('is_active', true)->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->helperText(function (Forms\Get $get) {
                                $variantId = $get('product_variant_id');
                                $type = $get('type');
                                
                                if (in_array($type, ['sale', 'damage', 'transfer']) && !$variantId) {
                                    return '⚠️ Primero seleccione un producto para ver las tiendas con stock';
                                }
                                
                                return 'Seleccione la tienda';
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                // Show current stock when variant and store are selected
                                $variantId = $get('product_variant_id');
                                if ($state && $variantId) {
                                    $inventory = Inventory::where('product_variant_id', $variantId)
                                        ->where('store_id', $state)
                                        ->first();
                                    
                                    if ($inventory) {
                                        $set('_current_stock', $inventory->quantity_available);
                                        $set('_reserved_stock', $inventory->quantity_reserved);
                                        $set('_inventory_id', $inventory->id);
                                    } else {
                                        $set('_current_stock', 0);
                                        $set('_reserved_stock', 0);
                                        $set('_inventory_id', null);
                                    }
                                }
                            }),
                        Forms\Components\Placeholder::make('_stock_info')
                            ->label('Información de Stock')
                            ->content(function (Forms\Get $get): string {
                                $available = $get('_current_stock');
                                $reserved = $get('_reserved_stock');
                                
                                if ($available === null) {
                                    return 'Seleccione producto y tienda para ver el stock';
                                }
                                
                                $info = "✅ Disponible para venta: {$available} unidades";
                                
                                if ($reserved > 0) {
                                    $info .= "\n🔒 Reservado: {$reserved} unidades";
                                    $info .= "\n📦 Total en tienda: " . ($available + $reserved) . " unidades";
                                }
                                
                                return $info;
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('destination_store_id')
                            ->label('Tienda Destino')
                            ->relationship('destinationStore', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'transfer')
                            ->required(fn (Forms\Get $get) => $get('type') === 'transfer')
                            ->helperText('Tienda a la que se transferirá el producto'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cantidades y Costos')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->live(onBlur: true)
                            ->helperText(function (Forms\Get $get): ?string {
                                $type = $get('type');
                                $available = $get('_current_stock');
                                
                                if (in_array($type, ['sale', 'damage', 'transfer']) && $available !== null) {
                                    return "Máximo disponible: {$available} unidades";
                                }
                                
                                return null;
                            })
                            ->rules([
                                function (Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $type = $get('type');
                                        $available = $get('_current_stock');
                                        
                                        // Validate for sale, damage, and transfer (operations that reduce stock)
                                        if (in_array($type, ['sale', 'damage', 'transfer'])) {
                                            if ($available === null || $available === 0) {
                                                $fail('No hay stock disponible para esta operación.');
                                                return;
                                            }
                                            
                                            if ($value > $available) {
                                                $fail("La cantidad excede el stock disponible. Solo hay {$available} unidades disponibles para venta.");
                                            }
                                        }
                                    };
                                },
                            ])
                            ->suffix('unidades'),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('Costo Unitario')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total_cost')
                            ->label('Costo Total')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('reference_document')
                            ->label('Documento de Referencia')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'sale' => 'info',
                        'transfer' => 'warning',
                        'adjustment' => 'gray',
                        'return' => 'success',
                        'damage' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase' => 'Compra',
                        'sale' => 'Venta',
                        'transfer' => 'Transferencia',
                        'adjustment' => 'Ajuste',
                        'return' => 'Devolución',
                        'damage' => 'Daño',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('reference_document')
                    ->label('Documento')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'purchase' => 'Compra',
                        'sale' => 'Venta',
                        'transfer' => 'Transferencia',
                        'adjustment' => 'Ajuste',
                        'return' => 'Devolución',
                        'damage' => 'Daño',
                    ]),
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovements::route('/'),
            'create' => Pages\CreateMovement::route('/create'),
            'view' => Pages\ViewMovement::route('/{record}'),
        ];
    }
}

