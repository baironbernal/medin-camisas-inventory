<?php

namespace App\Services;

use App\Models\DiscountRule;
use Filament\Forms\Set;

class DiscountCalculatorService
{
    /**
     * Find the applicable discount rule for a given quantity and apply it
     * to Filament form state (for use inside Repeater item callbacks).
     */
    public static function applyToFormState(int $quantity, float $unitPrice, Set $set): void
    {
        $rule = self::findRule($quantity);

        if ($rule && $unitPrice > 0) {
            $discountedPrice = self::calcDiscountedPrice($rule, $unitPrice);
            $discountPct = round((($unitPrice - $discountedPrice) / $unitPrice) * 100, 2);

            $set('discount_rule_id', $rule->id);
            $set('discount_percentage', $discountPct);
            $set('discounted_unit_price', round($discountedPrice, 2));
            $set('item_total', round($discountedPrice * $quantity, 2));
        } else {
            $set('discount_rule_id', null);
            $set('discount_percentage', 0);
            $set('discounted_unit_price', round($unitPrice, 2));
            $set('item_total', round($unitPrice * $quantity, 2));
        }
    }

    /**
     * Calculate the discounted unit price for an item given quantity.
     * Returns [discountedUnitPrice, discountPercentage, ruleId].
     */
    public static function calculate(int $quantity, float $unitPrice): array
    {
        $rule = self::findRule($quantity);

        if (! $rule || $unitPrice <= 0) {
            return [$unitPrice, 0.0, null];
        }

        $discountedPrice = self::calcDiscountedPrice($rule, $unitPrice);
        $discountPct = round((($unitPrice - $discountedPrice) / $unitPrice) * 100, 2);

        return [round($discountedPrice, 2), $discountPct, $rule->id];
    }

    private static function findRule(int $quantity): ?DiscountRule
    {
        return DiscountRule::where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity')
            ->first();
    }

    private static function calcDiscountedPrice(DiscountRule $rule, float $unitPrice): float
    {
        return match ($rule->discount_type) {
            'percentage' => $unitPrice * (1 - $rule->discount_value / 100),
            'fixed_amount' => max(0, $unitPrice - $rule->discount_value),
            'fixed_price' => (float) $rule->discount_value,
            default => $unitPrice,
        };
    }
}
