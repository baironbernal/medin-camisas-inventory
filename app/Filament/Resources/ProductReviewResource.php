<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductReviewResource\Pages;
use App\Models\ProductReview;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Gestión de Productos';

    protected static ?string $navigationLabel = 'Reseñas';

    protected static ?string $modelLabel = 'Reseña';

    protected static ?string $pluralModelLabel = 'Reseñas';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Reseña')
                    ->schema([
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('Producto'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\IconEntry::make('is_visible')
                            ->label('Visible en la tienda')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('comment')
                            ->label('Comentario')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comentario')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_visible')
                    ->label('Visible'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Producto')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibles'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProductReviews::route('/'),
            'view'  => Pages\ViewProductReview::route('/{record}'),
        ];
    }
}
