<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeAdResource\Pages;
use App\Models\HomeAd;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeAdResource extends Resource
{
    protected static ?string $model = HomeAd::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Gestión de Contenido';

    protected static ?string $navigationLabel = 'Anuncios Home';

    protected static ?string $modelLabel = 'Anuncio';

    protected static ?string $pluralModelLabel = 'Anuncios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Anuncio')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Fecha de Expiración')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Expira')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHomeAds::route('/'),
            'create' => Pages\CreateHomeAd::route('/create'),
            'edit'   => Pages\EditHomeAd::route('/{record}/edit'),
        ];
    }
}
