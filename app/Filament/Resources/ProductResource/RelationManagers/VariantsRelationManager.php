<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\VariantAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    protected function canEdit(Model $record): bool
    {
        return true;
    }

    protected function canView(Model $record): bool
    {
        return true;
    }

    private function checkShowInventory(Forms\Get $get): bool
    {
        $fullCurve = $get('full_curve');
        $sizes = $get('sizes') ?? [];
        $color = $get('colors');
        $material = $get('materials');
        $otherColor = $get('other_color');
        $otherColorValue = $get('other_color_value');
        $otherMaterial = $get('other_material');
        $otherMaterialValue = $get('other_material_value');

        $sizeSelected = $fullCurve || count($sizes) === 1;
        $colorSelected = ($color && ! $otherColor) || ($otherColor && $otherColorValue);
        $materialSelected = ($material && ! $otherMaterial) || ($otherMaterial && $otherMaterialValue);

        return $sizeSelected && $colorSelected && $materialSelected;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Imágenes de la Variante')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Galería')
                            ->multiple()
                            ->image()
                            ->reorderable()
                            ->appendFiles()
                            ->disk('public')
                            ->directory('variants')
                            ->visibility('public')
                            ->imageEditor()
                            ->panelLayout('grid')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('cost')
                    ->label('Costo')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('weight')
                    ->label('Peso (kg)')
                    ->numeric(),
                Forms\Components\TextInput::make('barcode')
                    ->label('Código de Barras')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attributes_text')
                    ->label('Atributos')
                    ->getStateUsing(function ($record) {
                        return $record->variantAttributes()
                            ->with(['attribute', 'attributeValue'])
                            ->get()
                            ->map(fn ($va) => "{$va->attribute->name}: {$va->attributeValue->value}")
                            ->join(' | ');
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Costo')
                    ->money('COP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock Total')
                    ->getStateUsing(fn ($record) => $record->inventories->sum('quantity_available')
                    )
                    ->badge()
                    ->color(fn ($state): string => $state > 50 ? 'success' : ($state > 0 ? 'warning' : 'danger')
                    ),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Código de Barras')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_variants')
                    ->label('Generar Variantes')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->form([
                        Forms\Components\Section::make('Selecciona los Atributos y Valores')
                            ->description('Selecciona las combinaciones que deseas crear.')
                            ->schema([
                                Forms\Components\Toggle::make('full_curve')
                                    ->label('Curva completa (todas las tallas)')
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        if ($state) {
                                            $sizeAttr = \App\Models\Attribute::where('code', 'SIZE')->first();
                                            if ($sizeAttr) {
                                                $allSizes = $sizeAttr->values()
                                                    ->where('is_active', true)
                                                    ->pluck('id')
                                                    ->toArray();
                                                $set('sizes', $allSizes);
                                            }
                                        }
                                    }),

                                Forms\Components\CheckboxList::make('sizes')
                                    ->label('Tallas')
                                    ->options(function () {
                                        $sizeAttr = Attribute::where('code', 'SIZE')->first();
                                        if (! $sizeAttr) {
                                            return [];
                                        }

                                        return $sizeAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->columns(4)
                                    ->reactive()
                                    ->hidden(fn (Forms\Get $get) => $get('full_curve')),

                                Forms\Components\Radio::make('colors')
                                    ->label('Color')
                                    ->options(function () {
                                        $colorAttr = Attribute::where('code', 'COLOR')->first();
                                        if (! $colorAttr) {
                                            return [];
                                        }

                                        return $colorAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->reactive()
                                    ->required(),

                                Forms\Components\Toggle::make('other_color')
                                    ->label('Otro color (especificar)')
                                    ->reactive(),

                                Forms\Components\TextInput::make('other_color_value')
                                    ->label('Nuevo Color')
                                    ->visible(fn (Forms\Get $get) => $get('other_color'))
                                    ->reactive(),

                                Forms\Components\Radio::make('materials')
                                    ->label('Material')
                                    ->options(function () {
                                        $materialAttr = Attribute::where('code', 'MATERIAL')->first();
                                        if (! $materialAttr) {
                                            return [];
                                        }

                                        return $materialAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->reactive()
                                    ->required(),

                                Forms\Components\Toggle::make('other_material')
                                    ->label('Otro material (especificar)')
                                    ->reactive(),

                                Forms\Components\TextInput::make('other_material_value')
                                    ->label('Nuevo Material')
                                    ->visible(fn (Forms\Get $get) => $get('other_material'))
                                    ->reactive(),

                                Forms\Components\Placeholder::make('show_inventory_indicator')
                                    ->label('')
                                    ->content(fn (Forms\Get $get) => $this->checkShowInventory($get) ? '✓ Se mostrará la sección de inventario' : '')
                                    ->visible(fn (Forms\Get $get) => $this->checkShowInventory($get)),
                            ]),

                        Forms\Components\Section::make('Configuración de Precio')
                            ->schema([
                                Forms\Components\Toggle::make('use_base_price')
                                    ->label('Usar precio base del producto')
                                    ->default(true)
                                    ->reactive(),

                                Forms\Components\TextInput::make('custom_price')
                                    ->label('Precio personalizado')
                                    ->numeric()
                                    ->prefix('$')
                                    ->visible(fn (callable $get) => ! $get('use_base_price')),
                            ]),

                        Forms\Components\Section::make('Inventario')
                            ->description('Configure el inventario para esta variante')
                            ->visible(fn (Forms\Get $get) => $this->checkShowInventory($get))
                            ->schema([
                                Forms\Components\Repeater::make('inventories')
                                    ->label('Inventario por Tienda')
                                    ->schema([
                                        Forms\Components\Select::make('store_id')
                                            ->label('Tienda')
                                            ->options(Store::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->searchable(),
                                        Forms\Components\TextInput::make('quantity_available')
                                            ->label('Cantidad Disponible')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        Forms\Components\TextInput::make('quantity_reserved')
                                            ->label('Cantidad Reservada')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
                                        Forms\Components\TextInput::make('quantity_in_transit')
                                            ->label('Cantidad en Tránsito')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0),
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
                                            ->label('Ubicación')
                                            ->maxLength(255),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(1)
                                    ->minItems(1),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $product = $livewire->getOwnerRecord();

                        DB::beginTransaction();
                        try {
                            $sizeAttr = Attribute::where('code', 'SIZE')->first();
                            $colorAttr = Attribute::where('code', 'COLOR')->first();
                            $materialAttr = Attribute::where('code', 'MATERIAL')->first();

                            $sizes = collect();
                            if (! empty($data['full_curve'])) {
                                $sizes = $sizeAttr->values()->where('is_active', true)->get();
                            } else {
                                $sizes = $sizeAttr->values()->whereIn('id', $data['sizes'] ?? [])->get();
                            }

                            $colors = collect();
                            if (! empty($data['colors'])) {
                                $colors = $colorAttr->values()->whereIn('id', [$data['colors']])->get();
                            }
                            if (! empty($data['other_color']) && ! empty($data['other_color_value'])) {
                                $newColor = AttributeValue::create([
                                    'attribute_id' => $colorAttr->id,
                                    'value' => $data['other_color_value'],
                                    'code' => strtoupper(substr($data['other_color_value'], 0, 3)),
                                    'is_active' => true,
                                ]);
                                $colors->push($newColor);
                            }

                            $materials = collect();
                            if (! empty($data['materials'])) {
                                $materials = $materialAttr->values()->whereIn('id', [$data['materials']])->get();
                            }
                            if (! empty($data['other_material']) && ! empty($data['other_material_value'])) {
                                $newMaterial = AttributeValue::create([
                                    'attribute_id' => $materialAttr->id,
                                    'value' => $data['other_material_value'],
                                    'code' => strtoupper(substr($data['other_material_value'], 0, 3)),
                                    'is_active' => true,
                                ]);
                                $materials->push($newMaterial);
                            }

                            $createdCount = 0;
                            $skippedCount = 0;

                            $inventories = $data['inventories'] ?? [];

                            foreach ($sizes as $size) {
                                foreach ($colors as $color) {
                                    foreach ($materials as $material) {
                                        $sku = strtoupper("{$product->reference_code}-{$size->code}-{$color->code}-{$material->code}");

                                        if (ProductVariant::where('sku', $sku)->exists()) {
                                            $skippedCount++;

                                            continue;
                                        }

                                        $price = $data['use_base_price']
                                            ? $product->base_price
                                            : $data['custom_price'];

                                        $variant = ProductVariant::create([
                                            'sku' => $sku,
                                            'product_id' => $product->id,
                                            'price' => $price,
                                            'cost' => $product->cost,
                                            'weight' => 0.3,
                                            'barcode' => '750'.str_pad((string) rand(1, 999999999), 9, '0', STR_PAD_LEFT),
                                            'is_active' => true,
                                        ]);

                                        VariantAttribute::create([
                                            'product_variant_id' => $variant->id,
                                            'attribute_id' => $sizeAttr->id,
                                            'attribute_value_id' => $size->id,
                                        ]);

                                        VariantAttribute::create([
                                            'product_variant_id' => $variant->id,
                                            'attribute_id' => $colorAttr->id,
                                            'attribute_value_id' => $color->id,
                                        ]);

                                        VariantAttribute::create([
                                            'product_variant_id' => $variant->id,
                                            'attribute_id' => $materialAttr->id,
                                            'attribute_value_id' => $material->id,
                                        ]);

                                        foreach ($inventories as $inventory) {
                                            if (! empty($inventory['store_id'])) {
                                                Inventory::create([
                                                    'product_variant_id' => $variant->id,
                                                    'store_id' => $inventory['store_id'],
                                                    'quantity_available' => $inventory['quantity_available'] ?? 0,
                                                    'quantity_reserved' => $inventory['quantity_reserved'] ?? 0,
                                                    'quantity_in_transit' => $inventory['quantity_in_transit'] ?? 0,
                                                    'min_quantity' => $inventory['min_quantity'] ?? null,
                                                    'max_quantity' => $inventory['max_quantity'] ?? null,
                                                    'reorder_point' => $inventory['reorder_point'] ?? 0,
                                                    'location' => $inventory['location'] ?? null,
                                                ]);
                                            }
                                        }

                                        $createdCount++;
                                    }
                                }
                            }

                            DB::commit();

                            $message = "Se crearon {$createdCount} variantes exitosamente.";
                            if ($skippedCount > 0) {
                                $message .= " Se omitieron {$skippedCount} variantes que ya existían.";
                            }

                            Notification::make()
                                ->title('Variantes generadas')
                                ->body($message)
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Error al generar variantes')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('3xl'),

                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
