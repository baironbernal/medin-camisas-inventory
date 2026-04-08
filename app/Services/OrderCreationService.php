<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderCreationService
{
    /**
     * Entry point: create a confirmed order from wizard form data.
     */
    public function createFromWizardData(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $user  = $this->resolveWholesaler($data);
            $order = $this->createOrderShell($user, $data);
            $this->createOrderItems($order, $data['items'] ?? []);
            $this->updateOrderTotals($order);

            // Inventory is NOT deducted here.
            // Stock deduction happens when the order is marked as completed (see OrderObserver).

            return $order;
        });
    }

    // -------------------------------------------------------------------------

    private function resolveWholesaler(array $data): ?User
    {
        // Existing wholesaler selected in Step 1
        if (filled($data['customer_id'] ?? null)) {
            return User::find($data['customer_id']);
        }

        // Register new wholesaler
        $firstName = trim($data['new_first_name'] ?? '');
        $lastName = trim($data['new_last_name'] ?? '');
        $rawEmail = $data['customer_email'] ?? null;
        $email = filled($rawEmail)
            ? $rawEmail
            : ($data['identity_number'] . '@mayorista.local');

        // If the provided email is already taken, use a unique identity-based address
        if (User::where('email', $email)->exists()) {
            $email = $data['identity_number'] . '@mayorista.local';
        }

        return User::create([
            'name' => trim("{$firstName} {$lastName}"),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'identity_number' => $data['identity_number'],
            'email' => $email,
            'phone_number' => $data['customer_phone'] ?? null,
            'password' => bcrypt(Str::random(20)),
            'is_active' => false,  // no panel access for wholesalers
        ]);
    }

    private function createOrderShell(?User $user, array $data): Order
    {
        $isExisting = filled($data['customer_id'] ?? null);

        return Order::create([
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PENDING,
            'user_id' => $user?->id,
            'customer_name' => $isExisting
                ? ($data['customer_name'] ?? $user?->name)
                : trim(($data['new_first_name'] ?? '') . ' ' . ($data['new_last_name'] ?? '')),
            'customer_email' => $isExisting
                ? ($data['customer_email'] ?? $user?->email)
                : ($data['customer_email'] ?? null),
            'customer_phone' => $data['customer_phone'] ?? $user?->phone_number,
            'payment_proof_path' => $data['payment_proof_path'] ?? null,
            'subtotal_original' => 0,
            'subtotal_discounted' => 0,
            'subtotal' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 0,
            'currency' => 'COP',
        ]);
    }

    private function createOrderItems(Order $order, array $items): void
    {
        // ── 1. Cart-level volume discount (based on total units) ──────────────
        $totalQuantity = array_sum(array_map(fn ($i) => max(1, (int) ($i['quantity'] ?? 1)), $items));
        [, $cartDiscountPct, $cartRuleId] = DiscountCalculatorService::calculate($totalQuantity, 1.0);

        // ── 2. Large-size protection rule ─────────────────────────────────────
        $largeSizeAnalysis = LargeSizeProtectionService::analyze($items);
        $largeSizeSurcharge = $largeSizeAnalysis['surcharge_per_item'];
        $largeVariantIds    = $largeSizeAnalysis['large_variant_ids'];

        // ── 3. Create each item ───────────────────────────────────────────────
        foreach ($items as $item) {
            $variantId = $item['product_variant_id'] ?? null;

            if (! $variantId) {
                continue;
            }

            $variant = ProductVariant::with('product')->find($variantId);

            if (! $variant) {
                continue;
            }

            $quantity  = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) ($item['unit_price'] ?? $variant->calculatePrice());

            // Apply large-size surcharge BEFORE volume discount
            if ($largeSizeSurcharge > 0 && in_array($variantId, $largeVariantIds, true)) {
                $unitPrice += $largeSizeSurcharge;
            }

            // Apply cart-level volume discount on the (possibly surcharge-adjusted) price
            [$discountedUnitPrice, $discountPct, $ruleId] = DiscountCalculatorService::calculate($totalQuantity, $unitPrice);

            OrderItem::create([
                'order_id'              => $order->id,
                'product_variant_id'    => $variantId,
                'product_name'          => $variant->product->name,
                'variant_sku'           => $variant->sku,
                'quantity'              => $quantity,
                'unit_price'            => $unitPrice,
                'discount_rule_id'      => $ruleId ?: null,
                'discount_percentage'   => $discountPct,
                'discounted_unit_price' => $discountedUnitPrice,
                'total_price'           => round($unitPrice * $quantity, 2),
                'discounted_total_price'=> round($discountedUnitPrice * $quantity, 2),
            ]);
        }
    }

    private function updateOrderTotals(Order $order): void
    {
        $order->loadMissing('items');

        $subtotalOriginal = (float) $order->items->sum('total_price');
        $subtotalDiscounted = (float) $order->items->sum('discounted_total_price');

        $order->update([
            'subtotal_original' => round($subtotalOriginal, 2),
            'subtotal_discounted' => round($subtotalDiscounted, 2),
            'subtotal' => round($subtotalDiscounted, 2),
            'total' => round($subtotalDiscounted, 2),
        ]);
    }
}
