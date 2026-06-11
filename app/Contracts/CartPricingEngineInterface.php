<?php

namespace App\Contracts;

interface CartPricingEngineInterface
{
    /**
     * Calculate prices, discounts and surcharges for a list of cart items.
     *
     * @param  array  $items  [['product_variant_id' => int, 'quantity' => int], ...]
     * @return array{
     *   items: array,
     *   subtotal_original: float,
     *   subtotal_discounted: float,
     *   total_discount: float,
     *   large_size_analysis: array
     * }
     */
    public function calculate(array $items): array;
}
