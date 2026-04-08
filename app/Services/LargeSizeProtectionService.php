<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class LargeSizeProtectionService
{
    /**
     * Analyze a cart's items and return everything needed for display and pricing.
     *
     * Returns:
     *   triggers            bool    — whether the surcharge applies
     *   surcharge_per_item  float   — amount to add per large-size unit (0 if not triggered)
     *   large_size_units    int
     *   total_units         int
     *   proportion          float   — e.g. 0.83 for 83 %
     *   large_variant_ids   int[]   — IDs of variants classified as large sizes
     */
    public static function analyze(array $items): array
    {
        $cfg           = config('order_rules.large_size_protection');
        $threshold     = (float) $cfg['threshold'];
        $surcharge     = (float) $cfg['surcharge'];
        $largeCodes    = array_map('strtoupper', $cfg['large_size_codes']);

        // Load all variants in one query
        $variantIds = array_filter(array_column($items, 'product_variant_id'));
        $variants   = ProductVariant::with([
            'variantAttributes.attribute',
            'variantAttributes.attributeValue',
        ])->whereIn('id', $variantIds)->get()->keyBy('id');

        $totalUnits     = 0;
        $largeSizeUnits = 0;
        $largeVariantIds = [];

        foreach ($items as $item) {
            $qty       = max(0, (int) ($item['quantity'] ?? 0));
            $variantId = $item['product_variant_id'] ?? null;

            if (! $variantId || ! $qty) {
                continue;
            }

            $variant = $variants->get($variantId);

            if (! $variant) {
                continue;
            }

            $totalUnits += $qty;

            if (self::variantIsLarge($variant, $largeCodes)) {
                $largeSizeUnits    += $qty;
                $largeVariantIds[]  = $variantId;
            }
        }

        $proportion = $totalUnits > 0 ? $largeSizeUnits / $totalUnits : 0.0;
        $triggers   = $proportion > $threshold;

        return [
            'triggers'           => $triggers,
            'surcharge_per_item' => $triggers ? $surcharge : 0.0,
            'large_size_units'   => $largeSizeUnits,
            'total_units'        => $totalUnits,
            'proportion'         => $proportion,
            'large_variant_ids'  => array_unique($largeVariantIds),
        ];
    }

    /**
     * Convenience: returns only the surcharge amount (0 if not triggered).
     * Use this in OrderCreationService.
     */
    public static function getSurcharge(array $items): float
    {
        return self::analyze($items)['surcharge_per_item'];
    }

    // -------------------------------------------------------------------------

    public static function variantIsLarge(ProductVariant $variant, array $largeCodes = []): bool
    {
        if (empty($largeCodes)) {
            $largeCodes = array_map(
                'strtoupper',
                config('order_rules.large_size_protection.large_size_codes', [])
            );
        }

        return $variant->variantAttributes
            ->filter(fn ($va) => strtoupper($va->attribute->code) === 'SIZE')
            ->contains(fn ($va) => in_array(strtoupper($va->attributeValue->code), $largeCodes, true));
    }
}
