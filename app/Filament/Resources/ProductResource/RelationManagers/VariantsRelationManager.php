<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Attribute;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->map(fn($va) => "{$va->attribute->name}: {$va->attributeValue->value}")
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
                    ->getStateUsing(fn ($record) => 
                        $record->inventories->sum('quantity_available')
                    )
                    ->badge()
                    ->color(fn ($state): string => 
                        $state > 50 ? 'success' : ($state > 0 ? 'warning' : 'danger')
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
                            ->description('Selecciona las combinaciones que deseas crear. Se generarán todas las combinaciones posibles.')
                            ->schema([
                                Forms\Components\CheckboxList::make('sizes')
                                    ->label('Tallas')
                                    ->options(function () {
                                        $sizeAttr = Attribute::where('code', 'SIZE')->first();
                                        if (!$sizeAttr) return [];
                                        return $sizeAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->columns(3)
                                    ->required(),
                                
                                Forms\Components\CheckboxList::make('colors')
                                    ->label('Colores')
                                    ->options(function () {
                                        $colorAttr = Attribute::where('code', 'COLOR')->first();
                                        if (!$colorAttr) return [];
                                        return $colorAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->columns(3)
                                    ->required(),
                                
                                Forms\Components\CheckboxList::make('materials')
                                    ->label('Materiales')
                                    ->options(function () {
                                        $materialAttr = Attribute::where('code', 'MATERIAL')->first();
                                        if (!$materialAttr) return [];
                                        return $materialAttr->values()
                                            ->where('is_active', true)
                                            ->pluck('value', 'id')
                                            ->toArray();
                                    })
                                    ->columns(3)
                                    ->required(),
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
                                    ->visible(fn (callable $get) => !$get('use_base_price')),
                            ]),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $product = $livewire->getOwnerRecord();
                        
                        DB::beginTransaction();
                        try {
                            $sizeAttr = Attribute::where('code', 'SIZE')->first();
                            $colorAttr = Attribute::where('code', 'COLOR')->first();
                            $materialAttr = Attribute::where('code', 'MATERIAL')->first();
                            
                            $sizes = $sizeAttr->values()->whereIn('id', $data['sizes'])->get();
                            $colors = $colorAttr->values()->whereIn('id', $data['colors'])->get();
                            $materials = $materialAttr->values()->whereIn('id', $data['materials'])->get();
                            
                            $createdCount = 0;
                            $skippedCount = 0;
                            
                            foreach ($sizes as $size) {
                                foreach ($colors as $color) {
                                    foreach ($materials as $material) {
                                        $sku = strtoupper("{$product->reference_code}-{$size->code}-{$color->code}-{$material->code}");
                                        
                                        // Check if variant already exists
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
                                            'barcode' => '750' . str_pad((string)rand(1, 999999999), 9, '0', STR_PAD_LEFT),
                                            'is_active' => true,
                                        ]);
                                        
                                        // Create variant attributes
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

