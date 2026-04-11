<?php

namespace App\Filament\Resources\OrderResource\Steps;

use App\Models\ProductVariant;
use App\Services\DiscountCalculatorService;
use App\Services\LargeSizeProtectionService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class ProductsStep
{
    public static function make(): Step
    {
        return Step::make('Productos')
            ->label('Productos')
            ->icon('heroicon-o-shopping-bag')
            ->description('Seleccione los productos y cantidades')
            ->schema([
                self::itemsRepeater(),
                self::totalsSummary(),
            ]);
    }

    // -------------------------------------------------------------------------

    private static function itemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->label('Productos del Pedido')
            ->schema([
                Select::make('product_variant_id')
                    ->label('Variante')
                    ->required()
                    ->searchable()
                    ->allowHtml()
                    ->getSearchResultsUsing(fn (string $search): array => self::searchVariants($search))
                    ->getOptionLabelUsing(fn ($value): string => self::variantPlainLabel($value))
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                        if (! $state) {
                            $set('unit_price', null);
                            $set('discount_percentage', 0);
                            $set('discounted_unit_price', null);
                            $set('item_total', null);
                            $set('discount_rule_id', null);

                            return;
                        }

                        $variant = ProductVariant::with(['product', 'inventories'])->find($state);

                        if ($variant) {
                            $price = round($variant->calculatePrice(), 2);
                            $stock = (int) $variant->total_stock;
                            $set('unit_price', $price);
                            $set('available_stock', $stock);
                            DiscountCalculatorService::applyToFormState(
                                max(1, (int) ($get('quantity') ?? 1)),
                                $price,
                                $set
                            );
                        }
                    })
                    ->columnSpan(2),

                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->maxValue(fn (Get $get): ?int => ($get('available_stock') > 0) ? (int) $get('available_stock') : null)
                    ->required()
                    ->live()
                    ->hint(fn (Get $get): string => filled($get('available_stock'))
                        ? 'Disponible: ' . number_format((int) $get('available_stock'), 0, ',', '.') . ' uds.'
                        : ''
                    )
                    ->hintColor(fn (Get $get): string => ((int) ($get('quantity') ?? 0)) > ((int) ($get('available_stock') ?? 9999))
                        ? 'danger'
                        : 'gray'
                    )
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $quantity = max(1, (int) $state);
                        $unitPrice = (float) ($get('unit_price') ?? 0);

                        if ($unitPrice > 0) {
                            DiscountCalculatorService::applyToFormState($quantity, $unitPrice, $set);
                        }
                    }),

                TextInput::make('unit_price')
                    ->label('Precio Unitario')
                    ->prefix('$')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('discount_percentage')
                    ->label('Descuento')
                    ->suffix('%')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('discounted_unit_price')
                    ->label('Precio c/Descuento')
                    ->prefix('$')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                TextInput::make('item_total')
                    ->label('Total Ítem')
                    ->prefix('$')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),

                Hidden::make('discount_rule_id')->dehydrated(),
                Hidden::make('available_stock')->default(0),
            ])
            ->columns(4)
            ->addActionLabel('+ Agregar Producto')
            ->live()
            ->reorderable(false)
            ->defaultItems(1)
            ->minItems(1);
    }

    private static function totalsSummary(): Section
    {
        return Section::make('Resumen de Totales')
            ->schema([
                Placeholder::make('order_total_preview')
                    ->label('')
                    ->content(fn (Get $get): HtmlString => self::buildTotalHtml($get('items') ?? [])),
            ]);
    }

    // -------------------------------------------------------------------------
    // Variant search helpers
    // -------------------------------------------------------------------------

    private static function searchVariants(string $search): array
    {
        // Split into individual terms so "camisa xs algodón" matches
        // a variant whose product is "Camisa Básica", size "XS", material "Algodón".
        // Every term must match somewhere (AND logic, case-insensitive via LIKE).
        $terms = array_values(array_filter(explode(' ', trim($search))));

        if (empty($terms)) {
            return [];
        }

        return ProductVariant::with([
            'product',
            'media',
            'variantAttributes.attribute',
            'variantAttributes.attributeValue',
        ])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term): void {
                        $like = "%{$term}%";
                        // Match product name
                        $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', $like))
                          // or variant SKU
                          ->orWhere('sku', 'like', $like)
                          // or any attribute value (size, color, material…)
                          ->orWhereHas(
                              'variantAttributes.attributeValue',
                              fn ($vq) => $vq->where('value', 'like', $like)
                          );
                    });
                }
            })
            ->limit(30)
            ->get()
            ->mapWithKeys(fn (ProductVariant $v): array => [$v->id => self::variantOptionHtml($v)])
            ->all();
    }

    private static function variantOptionHtml(ProductVariant $variant): string
    {
        $imageUrl = $variant->getFirstMediaUrl('variant-images');

        $imgStyle = 'width:40px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0;';
        $imgHtml = $imageUrl
            ? "<img src='{$imageUrl}' style='{$imgStyle}'>"
            : "<div style='{$imgStyle}background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:18px;'>🖼</div>";

        $attrs = $variant->variantAttributes;

        $colorAttr = $attrs->first(fn ($va) => $va->attribute->code === 'COLOR');
        $materialAttr = $attrs->first(fn ($va) => $va->attribute->code === 'MATERIAL');
        $sizeAttr = $attrs->first(fn ($va) => $va->attribute->code === 'SIZE');

        $colorHex = $colorAttr?->attributeValue->hex_color;
        $colorName = $colorAttr?->attributeValue->value ?? '';
        $materialName = $materialAttr?->attributeValue->value ?? '';
        $sizeName = $sizeAttr?->attributeValue->value ?? '';

        $colorSwatch = $colorHex
            ? "<span style='display:inline-block;width:10px;height:10px;border-radius:50%;background:{$colorHex};margin-right:4px;border:1px solid #d1d5db;vertical-align:middle;'></span>"
            : '';

        $attrParts = array_filter([$colorSwatch . $colorName, $materialName, $sizeName]);
        $attrLine = implode(' · ', $attrParts);

        $price = number_format($variant->calculatePrice(), 0, ',', '.');

        return "<div style='display:flex;align-items:center;gap:10px;padding:4px 0;'>"
            . $imgHtml
            . "<div style='min-width:0;'>"
            . "<div style='font-weight:500;font-size:0.875rem;'>{$variant->product->name}</div>"
            . "<div style='font-size:0.75rem;color:#6b7280;'>{$attrLine}</div>"
            . "<div style='font-size:0.75rem;color:#059669;font-weight:600;'>\${$price} COP</div>"
            . '</div></div>';
    }

    private static function variantPlainLabel(mixed $value): string
    {
        $variant = ProductVariant::with([
            'product',
            'variantAttributes.attributeValue',
        ])->find($value);

        if (! $variant) {
            return (string) $value;
        }

        $attrs = $variant->variantAttributes
            ->map(fn ($va) => $va->attributeValue->value)
            ->join(' · ');

        return "{$variant->product->name} — {$attrs}";
    }

    // -------------------------------------------------------------------------
    // Totals HTML (reused in PaymentStep via static call)
    // -------------------------------------------------------------------------

    public static function buildTotalHtml(array $items): HtmlString
    {
        $fmt = fn (float $v): string => '$' . number_format($v, 0, ',', '.') . ' COP';

        // ── Volume discount (cart total units) ────────────────────────────────
        $totalQty = array_sum(array_map(fn ($i) => max(0, (int) ($i['quantity'] ?? 0)), $items));
        [, $discountPct] = DiscountCalculatorService::calculate($totalQty, 1.0);

        // ── Large-size protection rule ─────────────────────────────────────────
        $largeSizeAnalysis  = LargeSizeProtectionService::analyze($items);
        $largeSizeSurcharge = $largeSizeAnalysis['surcharge_per_item'];
        $largeVariantIds    = $largeSizeAnalysis['large_variant_ids'];
        $largePropPct       = round($largeSizeAnalysis['proportion'] * 100);

        // ── Totals ─────────────────────────────────────────────────────────────
        $originalTotal   = 0.0;
        $discountedTotal = 0.0;

        foreach ($items as $item) {
            $qty       = max(0, (int) ($item['quantity'] ?? 0));
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            if ($largeSizeSurcharge > 0 && in_array($item['product_variant_id'] ?? null, $largeVariantIds, true)) {
                $unitPrice += $largeSizeSurcharge;
            }

            $discountedUnitPrice = $unitPrice * (1 - $discountPct / 100);
            $originalTotal      += $unitPrice * $qty;
            $discountedTotal    += $discountedUnitPrice * $qty;
        }

        $savings = $originalTotal - $discountedTotal;

        $html = "<div style='font-size:0.95rem;'>";

        // Large-size warning banner
        if ($largeSizeAnalysis['triggers']) {
            $surchargeFormatted = number_format($largeSizeSurcharge, 0, ',', '.');
            $html .= "<div style='background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:8px 12px;margin-bottom:10px;font-size:0.82rem;'>"
                . "<strong>⚠ Regla tallas grandes activa ({$largePropPct}% del pedido)</strong><br>"
                . "Se aplica un recargo de <strong>\${$surchargeFormatted} COP</strong> por cada prenda de talla grande."
                . ' Combina tallas para evitar el recargo.'
                . '</div>';
        }

        // Volume discount line
        if ($discountPct > 0) {
            $html .= "<div style='color:#6b7280;font-size:0.8rem;margin-bottom:4px;'>"
                . "Descuento por volumen ({$totalQty} uds.): <strong>{$discountPct}%</strong>"
                . '</div>';
            $html .= "<div style='color:#9ca3af;text-decoration:line-through;margin-bottom:2px;'>Subtotal: {$fmt($originalTotal)}</div>";
            $html .= "<div style='color:#16a34a;margin-bottom:4px;'>Ahorro: {$fmt($savings)}</div>";
        }

        $html .= "<div style='font-weight:700;font-size:1.25rem;color:#1f2937;'>Total: {$fmt($discountedTotal)}</div>";
        $html .= '</div>';

        return new HtmlString($html);
    }
}
