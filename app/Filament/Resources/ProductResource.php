<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Gestión de Productos';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('reference_code')
                            ->label('Código de Referencia')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('season_id')
                            ->label('Temporada')
                            ->relationship('season', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                       SelectTree::make('category_id')
                            ->label('Categoría')
                            ->withCount()
                            ->expandSelected(true)
                            ->relationship(
                                relationship: 'category',      // La relación BelongsTo en tu modelo Category
                                titleAttribute: 'name',       // La columna que se mostrará (nombre de la categoría)
                                parentAttribute: 'parent_id'  // La columna que apunta al padre
                            )
                            ->enableBranchNode() // Permite seleccionar categorías que tienen hijos dentro
                            ->searchable()       // Por si tienes muchas categorías
                            ->placeholder('Selecciona una categoría padre'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Precios y Costos')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Precio Base')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('supplier')
                            ->label('Proveedor')
                            ->maxLength(255),
                    ])
                    ->columns(2),



                Forms\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Etiquetas'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->required()
                            ->default(true),

                            Forms\Components\FileUpload::make('images')
                            ->label('Foto principal y foto de tela')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('products/images')
                            ->visibility('public')
                            ->maxFiles(2)
                            ->maxSize(1120) // 5MB
                            ->imageEditor()
                            ->reorderable()
                            ->panelLayout('grid')
                            ->uploadingMessage('Subiendo fotos...')
                            ->helperText('Máximo 2 imágenes, menos de 1MB cada una'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => collect($record->images)->first())
                    ->width(48)
                    ->height(48)
                    ->rounded()
                    ->defaultImageUrl(asset('images/placeholder.png')),

                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('season.name')
                    ->label('Temporada')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Precio Base')
                    ->money('COP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season')
                    ->relationship('season', 'name'),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

