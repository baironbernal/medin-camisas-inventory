<?php

namespace App\Services;

use App\Models\ProductVariant;

class CartService
{
    /**
     * Calculate prices, discounts and surcharges for a list of cart items.
     * This is the single source of truth for all cart pricing.
     *
     * @param  array  $items  [['product_variant_id' => int, 'quantity' => int], ...]
     */
    public function calculate(array $items): array
    {
        if (empty($items)) {
            return $this->emptyResult();
        }

        // ── 1. Load all variants in one query ─────────────────────────────────
        $variantIds = array_filter(array_column($items, 'product_variant_id'));

        $variants = ProductVariant::with([
            'product',
            'variantAttributes.attribute',
            'variantAttributes.attributeValue',
        ])
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        // ── 2. Large-size surcharge analysis (one pass) ────────────────────────
        $largeSizeAnalysis  = LargeSizeProtectionService::analyze($items);
        $largeSizeSurcharge = $largeSizeAnalysis['surcharge_per_item'];
        $largeVariantIds    = $largeSizeAnalysis['large_variant_ids'];

        // ── 3. Total quantity drives volume-discount tier ──────────────────────
        $totalQuantity = array_sum(
            array_map(fn ($i) => max(1, (int) ($i['quantity'] ?? 1)), $items)
        );

        // ── 4. Build per-item calculated data ─────────────────────────────────
        $calculatedItems    = [];
        $subtotalOriginal   = 0.0;
        $subtotalDiscounted = 0.0;

        foreach ($items as $item) {
            $variantId = $item['product_variant_id'] ?? null;
            $quantity  = max(1, (int) ($item['quantity'] ?? 1));
            $variant   = $variants->get($variantId);

            // Skip variants that don't exist or aren't active
            if (! $variant) {
                continue;
            }

            $unitPrice             = (float) $variant->price;
            $hasLargeSizeSurcharge = $largeSizeSurcharge > 0
                && in_array($variantId, $largeVariantIds, true);

            if ($hasLargeSizeSurcharge) {
                $unitPrice += $largeSizeSurcharge;
            }

            [$discountedUnitPrice, $discountPct, $ruleId] = DiscountCalculatorService::calculate(
                $totalQuantity,
                $unitPrice
            );

            $totalPrice          = round($unitPrice * $quantity, 2);
            $discountedTotalPrice = round($discountedUnitPrice * $quantity, 2);

            $subtotalOriginal   += $totalPrice;
            $subtotalDiscounted += $discountedTotalPrice;

            $calculatedItems[] = [
                'product_variant_id'     => $variantId,
                'quantity'               => $quantity,
                'unit_price'             => $unitPrice,
                'discounted_unit_price'  => $discountedUnitPrice,
                'total_price'            => $totalPrice,
                'discounted_total_price' => $discountedTotalPrice,
                'discount_percentage'    => $discountPct,
                'discount_rule_id'       => $ruleId,
                'has_large_size_surcharge' => $hasLargeSizeSurcharge,
                'surcharge_amount'       => $hasLargeSizeSurcharge ? $largeSizeSurcharge : 0,
            ];
        }

        return [
            'items'               => $calculatedItems,
            'subtotal_original'   => round($subtotalOriginal, 2),
            'subtotal_discounted' => round($subtotalDiscounted, 2),
            'total_discount'      => round($subtotalOriginal - $subtotalDiscounted, 2),
            'large_size_analysis' => [
                'triggers'         => $largeSizeAnalysis['triggers'],
                'proportion'       => $largeSizeAnalysis['proportion'],
                'surcharge_per_item' => $largeSizeSurcharge,
                'large_size_units' => $largeSizeAnalysis['large_size_units'],
                'total_units'      => $largeSizeAnalysis['total_units'],
            ],
        ];
    }

    private function emptyResult(): array
    {
        return [
            'items'               => [],
            'subtotal_original'   => 0,
            'subtotal_discounted' => 0,
            'total_discount'      => 0,
            'large_size_analysis' => [
                'triggers'         => false,
                'proportion'       => 0,
                'surcharge_per_item' => 0,
                'large_size_units' => 0,
                'total_units'      => 0,
            ],
        ];
    }
}
