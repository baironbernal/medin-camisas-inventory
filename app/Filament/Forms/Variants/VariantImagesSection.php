<?php

namespace App\Filament\Forms\Variants;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;

class VariantImagesSection
{
    public static function make(): Section
    {
        return Section::make('Galería de Imágenes')
            ->description('Las imágenes se aplicarán a todas las variantes generadas')
            ->schema([
                FileUpload::make('gallery_images')
                    ->label('Imágenes')
                    ->multiple()
                    ->image()
                    ->reorderable()
                    ->disk('public')
                    ->directory('variants')
                    ->visibility('public')
                    ->imageEditor()
                    ->panelLayout('grid')
                    ->maxFiles(10)
                    ->columnSpanFull(),
            ]);
    }
}
