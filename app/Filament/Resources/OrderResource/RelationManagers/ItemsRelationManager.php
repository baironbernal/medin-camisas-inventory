<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Contracts\CartPricingEngineInterface;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items del Pedido';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $isPending = $this->ownerRecord->status === Order::STATUS_PENDING;

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'productVariant.variantAttributes.attribute',
                'productVariant.variantAttributes.attributeValue',
                'discount',
            ]))
            ->columns([
                Tables\Columns\ImageColumn::make('variant_image')
                    ->label('Foto')
                    ->getStateUsing(function (OrderItem $record): ?string {
                        $images = $record->productVariant?->images;
                        if (! is_array($images) || empty($images)) return null;
                        return asset('storage/' . $images[0]);
                    })
                    ->width(48)
                    ->height(48)
                    ->extraImgAttributes(['style' => 'border-radius:8px;object-fit:cover;']),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Producto')
                    ->weight(\Filament\Support\Enums\FontWeight::Medium),

                Tables\Columns\TextColumn::make('variant_sku')
                    ->label('SKU')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('color_swatch')
                    ->label('Color')
                    ->html()
                    ->getStateUsing(function (OrderItem $record): string {
                        $colorAttr = $record->productVariant?->variantAttributes
                            ->first(fn ($va) => optional($va->attribute)->code === 'COLOR');
                        $hex  = $colorAttr?->attributeValue?->hex_color;
                        $name = $colorAttr?->attributeValue?->value ?? '—';
                        if ($hex) {
                            return "<span style=\"display:inline-block;width:22px;height:22px;border-radius:50%;background:{$hex};border:1px solid #d1d5db;box-shadow:0 1px 2px rgba(0,0,0,.15);\" title=\"{$name}\"></span>";
                        }
                        return '<span style="color:#9ca3af;">—</span>';
                    }),

                Tables\Columns\TextColumn::make('color_name')
                    ->label('Nombre color')
                    ->getStateUsing(function (OrderItem $record): string {
                        $colorAttr = $record->productVariant?->variantAttributes
                            ->first(fn ($va) => optional($va->attribute)->code === 'COLOR');
                        return $colorAttr?->attributeValue?->value ?? '—';
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cant.')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('unit_price_fmt')
                    ->label('Precio Unit.')
                    ->alignRight()
                    ->getStateUsing(fn (OrderItem $record): string =>
                        '$' . number_format((float) $record->unit_price, 0, ',', '.')
                    ),
                Tables\Columns\TextColumn::make('discount_rule_fmt')
                    ->label('Regla')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (OrderItem $record): string =>
                        $record->discount_percentage > 0
                            ? number_format((float) $record->discount_percentage, 0) . '%'
                            : '—'
                    ),

                Tables\Columns\SelectColumn::make('discount_id')
                    ->label('Descuento')
                    ->options(fn (): array => Discount::active()->pluck('name', 'id')->toArray())
                    ->placeholder('Sin descuento')
                    ->selectablePlaceholder(true)
                    ->disabled(! $isPending)
                    ->afterStateUpdated(function ($state, OrderItem $record): void {
                        $discountId = $state ? (int) $state : null;
                        $unitPrice  = (float) $record->unit_price;

                        // Base for manual discount is the cart/volume discounted price,
                        // not the raw unit price — so manual discounts stack on top of
                        // the existing volume discount instead of overriding it.
                        $cartDiscountedPrice = $unitPrice * (1 - (float) $record->discount_percentage / 100);

                        if ($discountId) {
                            $discount = Discount::find($discountId);
                            if ($discount) {
                                if ($discount->type === 'percentage') {
                                    $discountedUnit = $cartDiscountedPrice * (1 - (float) $discount->value / 100);
                                } else {
                                    $discountedUnit = max(0, $cartDiscountedPrice - (float) $discount->value);
                                }
                            } else {
                                $discountedUnit = $cartDiscountedPrice;
                            }
                        } else {
                            // No manual discount — restore the cart-discounted price
                            $discountedUnit = $cartDiscountedPrice;
                        }

                        $record->update([
                            'discount_id'            => $discountId,
                            'discounted_unit_price'  => $discountedUnit,
                            'discounted_total_price' => $discountedUnit * $record->quantity,
                        ]);

                        static::recalculateOrderById($record->order_id);
                    }),

                Tables\Columns\TextColumn::make('subtotal_fmt')
                    ->label('Subtotal')
                    ->alignRight()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->getStateUsing(fn (OrderItem $record): string =>
                        '$' . number_format((float) $record->discounted_total_price, 0, ',', '.')
                    ),
            ])
            ->headerActions($isPending ? [
                Tables\Actions\Action::make('add_item')
                    ->label('Agregar Item')
                    ->icon('heroicon-o-plus-circle')
                    ->color('info')
                    ->form([
                        Select::make('product_variant_id')
                            ->label('Variante')
                            ->allowHtml()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return ProductVariant::with(['product', 'inventories'])
                                    ->where('is_active', true)
                                    ->whereHas('inventories', fn ($q) => $q->where('quantity_available', '>', 0))
                                    ->where(fn ($q) => $q
                                        ->where('sku', 'like', "%{$search}%")
                                        ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                    )
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(function ($v) {
                                        $images   = $v->images ?? [];
                                        $imageUrl = ! empty($images) ? asset('storage/' . $images[0]) : null;
                                        $img      = $imageUrl
                                            ? "<img src='{$imageUrl}' style='width:28px;height:28px;object-fit:cover;border-radius:4px;flex-shrink:0;'>"
                                            : "<span style='display:inline-block;width:28px;height:28px;background:#e5e7eb;border-radius:4px;flex-shrink:0;'></span>";
                                        $stock = $v->inventories->sum('quantity_available');
                                        return [$v->id => "<div style='display:flex;align-items:center;gap:8px;'>{$img}<span><b>{$v->sku}</b> — {$v->product->name} <small style='color:#6b7280;'>({$stock} disp.)</small></span></div>"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): string {
                                $v = ProductVariant::with(['product', 'inventories'])->find($value);
                                if (! $v) return '';
                                $images   = $v->images ?? [];
                                $imageUrl = ! empty($images) ? asset('storage/' . $images[0]) : null;
                                $img      = $imageUrl
                                    ? "<img src='{$imageUrl}' style='width:28px;height:28px;object-fit:cover;border-radius:4px;'>"
                                    : "<span style='display:inline-block;width:28px;height:28px;background:#e5e7eb;border-radius:4px;'></span>";
                                $stock = $v->inventories->sum('quantity_available');
                                return "<div style='display:flex;align-items:center;gap:8px;'>{$img}<span><b>{$v->sku}</b> — {$v->product->name} <small style='color:#6b7280;'>({$stock} disp.)</small></span></div>";
                            })
                            ->live()
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->maxValue(function (Get $get): ?int {
                                $variantId = $get('product_variant_id');
                                if (! $variantId) return null;
                                $variant = ProductVariant::with('inventories')->find($variantId);
                                return $variant ? (int) $variant->inventories->sum('quantity_available') : null;
                            })
                            ->helperText(function (Get $get): string {
                                $variantId = $get('product_variant_id');
                                if (! $variantId) return 'Selecciona una variante primero.';
                                $variant = ProductVariant::with('inventories')->find($variantId);
                                $stock   = $variant ? (int) $variant->inventories->sum('quantity_available') : 0;
                                return "Disponible: {$stock} unidades.";
                            })
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $order   = $this->ownerRecord;
                        $variant = ProductVariant::with(['product', 'inventories'])->findOrFail($data['product_variant_id']);
                        $qty     = (int) $data['quantity'];

                        $available = (int) $variant->inventories->sum('quantity_available');
                        if ($qty > $available) {
                            Notification::make()
                                ->title("Solo hay {$available} unidades disponibles.")
                                ->danger()
                                ->send();
                            return;
                        }

                        $unit    = (float) $variant->product->base_price;
                        $qty     = (int) $data['quantity'];

                        OrderItem::create([
                            'order_id'               => $order->id,
                            'product_variant_id'     => $variant->id,
                            'product_name'           => $variant->product->name,
                            'variant_sku'            => $variant->sku,
                            'quantity'               => $qty,
                            'unit_price'             => $unit,
                            'discount_rule_id'       => null,
                            'discount_id'            => null,
                            'discount_percentage'    => 0,
                            'discounted_unit_price'  => $unit,
                            'total_price'            => $unit * $qty,
                            'discounted_total_price' => $unit * $qty,
                        ]);

                        static::recalculateAllItemDiscounts($order->id);
                        Notification::make()->title('Item agregado')->success()->send();
                    }),
            ] : [])
            ->actions($isPending ? [
                Tables\Actions\Action::make('reduce')
                    ->label('Quitar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->mountUsing(function (\Filament\Forms\Form $form, OrderItem $record): void {
                        $form->fill([
                            'item_label'         => "{$record->product_name} — {$record->variant_sku}",
                            'current_quantity'   => $record->quantity,
                            'quantity_to_remove' => 1,
                        ]);
                    })
                    ->form([
                        Placeholder::make('item_label')
                            ->label('Item')
                            ->content(fn (Get $get): string => $get('item_label') ?? ''),
                        TextInput::make('current_quantity')
                            ->label('Cantidad actual')
                            ->disabled(),
                        TextInput::make('quantity_to_remove')
                            ->label('Cantidad a quitar')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (Get $get): int => max(1, (int) $get('current_quantity')))
                            ->required()
                            ->helperText(fn (Get $get): string => 'Máximo: ' . ((int) $get('current_quantity')) . ' unidades.'),
                    ])
                    ->modalHeading('Quitar / Reducir Item')
                    ->modalSubmitActionLabel('Confirmar')
                    ->action(function (OrderItem $record, array $data): void {
                        $newQty = $record->quantity - (int) $data['quantity_to_remove'];

                        if ($newQty <= 0) {
                            $record->delete();
                        } else {
                            $record->update([
                                'quantity'               => $newQty,
                                'total_price'            => (float) $record->unit_price * $newQty,
                                'discounted_total_price' => (float) $record->discounted_unit_price * $newQty,
                            ]);
                        }

                        static::recalculateAllItemDiscounts($record->order_id);
                        Notification::make()->title('Item actualizado')->warning()->send();
                    }),
            ] : [])
            ->paginated(false);
    }

    /**
     * Reapply cart pricing to every item using CartService — the same engine
     * the frontend uses via POST /api/cart/calculate. Handles volume discounts,
     * large-size surcharges, and everything else in one place.
     * Manual discounts (discount_id) are preserved and stacked on top.
     */
    public static function recalculateAllItemDiscounts(int $orderId): void
    {
        $order = Order::with('items')->find($orderId);
        if (! $order) return;

        $cartItems = $order->items
            ->map(fn ($item) => [
                'product_variant_id' => $item->product_variant_id,
                'quantity'           => $item->quantity,
            ])
            ->toArray();

        $result     = app(CartPricingEngineInterface::class)->calculate($cartItems);
        $calculated = collect($result['items'])->keyBy('product_variant_id');

        foreach ($order->items as $item) {
            $calc = $calculated->get($item->product_variant_id);
            if (! $calc) continue;

            $cartDiscountedPrice = $calc['discounted_unit_price'];

            if ($item->discount_id) {
                $manual = Discount::find($item->discount_id);
                $finalPrice = $manual
                    ? ($manual->type === 'percentage'
                        ? $cartDiscountedPrice * (1 - (float) $manual->value / 100)
                        : max(0, $cartDiscountedPrice - (float) $manual->value))
                    : $cartDiscountedPrice;
            } else {
                $finalPrice = $cartDiscountedPrice;
            }

            $item->update([
                'unit_price'             => $calc['unit_price'],
                'discount_rule_id'       => $calc['discount_rule_id'],
                'discount_percentage'    => $calc['discount_percentage'],
                'discounted_unit_price'  => round($finalPrice, 2),
                'total_price'            => $calc['total_price'],
                'discounted_total_price' => round($finalPrice * $item->quantity, 2),
            ]);
        }

        static::recalculateOrderById($orderId);
    }

    public static function recalculateOrderById(int $orderId): void
    {
        $order = Order::with('items')->find($orderId);
        if (! $order) return;

        $items              = $order->items;
        $subtotalOriginal   = (float) $items->sum('total_price');
        $subtotalDiscounted = (float) $items->sum('discounted_total_price');
        $total              = $subtotalDiscounted + (float) $order->tax + (float) $order->shipping_cost;

        $order->update([
            'subtotal'            => $subtotalDiscounted,
            'subtotal_original'   => $subtotalOriginal,
            'subtotal_discounted' => $subtotalDiscounted,
            'total'               => $total,
        ]);
    }
}
