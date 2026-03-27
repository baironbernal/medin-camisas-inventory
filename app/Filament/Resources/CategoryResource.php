<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Set;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Gestión de Productos';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Categorías';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->label('Nombre de la Categoría')
                ->required()
                ->live(onBlur: true)
                ->placeholder('Ej: Manga Corta')
                ->afterStateUpdated(function (string $state, Set $set) {
                    $formattedCode = Str::upper(str_replace('-', '_', Str::slug($state)));
                    $set('code', $formattedCode); 
                }),

                TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->unique(ignoreRecord: true) 
                    ->placeholder('Se generará automáticamente...'),

                SelectTree::make('parent_id') // Guardará el ID del padre elegido
                    ->label('Categoría Superior (Padre)')
                    ->relationship(
                        relationship: 'parent',      // Nombre de la relación BelongsTo en tu modelo
                        titleAttribute: 'name',       // Columna a mostrar en pantalla
                        parentAttribute: 'parent_id'  // Columna que une los niveles
                    )
                    ->placeholder('Sin categoría padre (Es categoría principal)')
                    ->searchable()                    // Permite buscar si tienes cientos de categorías
                    ->enableBranchNode()              // IMPORTANTE: Permite seleccionar categorías que ya tienen hijos
                    ->withCount()                     // Opcional: muestra cuántos hijos tiene cada rama
                    ->defaultOpenLevel(1),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Categoría Padre')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
