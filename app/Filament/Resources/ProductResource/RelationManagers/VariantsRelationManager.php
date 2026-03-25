<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Forms\Variants\VariantAttributesSection;
use App\Filament\Forms\Variants\VariantImagesSection;
use App\Filament\Forms\Variants\VariantInventorySection;
use App\Filament\Forms\Variants\VariantPricingSection;
use App\Services\ProductVariantService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    // ── Permissions ───────────────────────────────────────────────────────────

    protected function canEdit(Model $record): bool
    {
        return true;
    }

    protected function canView(Model $record): bool
    {
        return true;
    }

    // ── Edit / Create form (individual variant) ───────────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([
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

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->modifyQueryUsing(
                fn (Builder $query) => $query->with(['inventories.store'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attributes_text')
                    ->label('Atributos')
                    ->getStateUsing(fn ($record) => $record->variantAttributes()
                        ->with(['attribute', 'attributeValue'])
                        ->get()
                        ->map(fn ($va) => "{$va->attribute->name}: {$va->attributeValue->value}")
                        ->join(' | ')),

                Tables\Columns\TextColumn::make('cost')
                    ->label('Costo')
                    ->money('COP')
                    ->sortable(),

                // ── Inline stock editor (Livewire component per row) ──────────
                Tables\Columns\ViewColumn::make('inline_stock')
                    ->label('Stock por Tienda')
                    ->view('filament.tables.columns.inline-stock-column')
                    ->grow(false),

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
                $this->generateVariantsAction(),
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

    // ── "Generar Variantes" action ────────────────────────────────────────────

    private function generateVariantsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate_variants')
            ->label('Generar Variantes')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->form([
                VariantAttributesSection::make(),
                VariantImagesSection::make(),
                VariantPricingSection::make(),
                VariantInventorySection::make(),
            ])
            ->action(function (array $data, RelationManager $livewire): void {
                $product = $livewire->getOwnerRecord();

                if (empty($data['inventories'])) {
                    Notification::make()
                        ->title('Error')
                        ->body('No se ha configurado el inventario. Por favor configure las cantidades.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $result = app(ProductVariantService::class)->generate($product, $data);

                    $message = "Se crearon {$result['created']} variantes exitosamente.";
                    if ($result['skipped'] > 0) {
                        $message .= " Se omitieron {$result['skipped']} variantes que ya existían.";
                    }

                    Notification::make()
                        ->title('Variantes generadas')
                        ->body($message)
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error al generar variantes')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->modalWidth('3xl');
    }
}
