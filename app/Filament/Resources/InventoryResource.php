<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Producto')
                    ->description('Seleccione la variante de producto y la tienda. No puede crear duplicados.')
                    ->schema([
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
                                // Check if combination already exists
                                $storeId = $get('store_id');
                                if ($state && $storeId) {
                                    $exists = Inventory::where('product_variant_id', $state)
                                        ->where('store_id', $storeId)
                                        ->exists();
                                    if ($exists) {
                                        $set('_duplicate_warning', true);
                                    } else {
                                        $set('_duplicate_warning', false);
                                    }
                                }
                            })
                            ->unique(table: Inventory::class, column: 'product_variant_id', ignoreRecord: true, modifyRuleUsing: function ($rule, Forms\Get $get) {
                                return $rule->where('store_id', $get('store_id'));
                            })
                            ->helperText('Seleccione la variante del producto para la cual desea crear un registro de inventario'),
                        Forms\Components\Select::make('store_id')
                            ->label('Tienda')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                // Check if combination already exists
                                $variantId = $get('product_variant_id');
                                if ($state && $variantId) {
                                    $exists = Inventory::where('product_variant_id', $variantId)
                                        ->where('store_id', $state)
                                        ->exists();
                                    if ($exists) {
                                        $set('_duplicate_warning', true);
                                    } else {
                                        $set('_duplicate_warning', false);
                                    }
                                }
                            }),
                        Forms\Components\Placeholder::make('_duplicate_warning')
                            ->label('')
                            ->content('⚠️ Ya existe un registro de inventario para esta combinación de producto y tienda. No puede crear un duplicado.')
                            ->visible(fn (Forms\Get $get): bool => $get('_duplicate_warning') === true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Cantidades')
                    ->schema([
                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Cantidad Disponible')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('quantity_reserved')
                            ->label('Cantidad Reservada')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('quantity_in_transit')
                            ->label('Cantidad en Tránsito')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Configuración de Stock')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Cantidad Mínima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label('Cantidad Máxima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Punto de Reorden')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación en Almacén')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productVariant.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Tienda')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_available')
                    ->label('Disponible')
                    ->sortable()
                    ->badge()
                    ->color(fn (Inventory $record): string =>
                        $record->is_low_stock ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('quantity_reserved')
                    ->label('Reservado'),
                Tables\Columns\TextColumn::make('quantity_in_transit')
                    ->label('En Tránsito'),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store')
                    ->relationship('store', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}

