<?php

namespace Tests\Feature\Api;

use App\Models\DiscountRule;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for POST /api/checkout.
 *
 * Covers the full checkout flow: validation, order creation, pricing,
 * discounts, and Wompi widget config generation.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.public_key'    => 'pub_test_XXXX',
            'services.wompi.integrity_key' => 'test_integrity',
        ]);
    }

    // ── Auth guard ───────────────────────────────────────────────────────────

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/checkout', [])->assertStatus(401);
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function test_checkout_fails_without_customer_email(): void
    {
        [$user, $variant, $inventory] = $this->setupCheckoutDependencies();

        $this->actingAs($user)
            ->postJson('/api/checkout', $this->payload($variant, omit: 'customer_email'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_email');
    }

    public function test_checkout_fails_without_customer_name(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $this->actingAs($user)
            ->postJson('/api/checkout', $this->payload($variant, omit: 'customer_name'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_name');
    }

    public function test_checkout_fails_with_invalid_email_format(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $payload = $this->payload($variant);
        $payload['customer_email'] = 'not-an-email';

        $this->actingAs($user)
            ->postJson('/api/checkout', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_email');
    }

    public function test_checkout_fails_with_empty_items_array(): void
    {
        [$user] = $this->setupCheckoutDependencies();

        $this->actingAs($user)
            ->postJson('/api/checkout', [
                'customer_email' => 'test@example.com',
                'customer_name'  => 'Test',
                'items'          => [],
            ])
            ->assertStatus(422);
    }

    public function test_checkout_fails_when_variant_does_not_exist(): void
    {
        [$user] = $this->setupCheckoutDependencies();

        $payload = [
            'customer_email' => 'test@example.com',
            'customer_name'  => 'Test User',
            'items'          => [['product_variant_id' => 99999, 'quantity' => 1]],
        ];

        $this->actingAs($user)
            ->postJson('/api/checkout', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.product_variant_id');
    }

    // ── Business logic errors ────────────────────────────────────────────────

    public function test_checkout_fails_when_variant_is_inactive(): void
    {
        $user    = User::factory()->create();
        $variant = ProductVariant::factory()->inactive()->create();
        Inventory::factory()->withStock(10)->create(['product_variant_id' => $variant->id]);

        $this->actingAs($user)
            ->postJson('/api/checkout', $this->payload($variant))
            ->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        $user    = User::factory()->create();
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->withStock(1)->create(['product_variant_id' => $variant->id]);

        $payload = $this->payload($variant, quantity: 5); // requesting 5, only 1 available

        $this->actingAs($user)
            ->postJson('/api/checkout', $payload)
            ->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    // ── Success scenarios ────────────────────────────────────────────────────

    public function test_successful_checkout_returns_201_with_order_and_payment_config(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $response = $this->actingAs($user)
            ->postJson('/api/checkout', $this->payload($variant));

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'order'   => ['id', 'order_number', 'status', 'total', 'currency'],
                    'items'   => [['product_name', 'variant_sku', 'quantity', 'unit_price']],
                    'payment' => ['public_key', 'currency', 'amount_in_cents', 'reference', 'signature'],
                ],
            ]);
    }

    public function test_checkout_creates_a_pending_order_in_the_database(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $this->actingAs($user)->postJson('/api/checkout', $this->payload($variant));

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status'  => Order::STATUS_PENDING,
            'currency' => 'COP',
        ]);
    }

    public function test_checkout_links_the_order_to_the_authenticated_user(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $response = $this->actingAs($user)->postJson('/api/checkout', $this->payload($variant));

        $orderId = $response->json('data.order.id');
        $order   = Order::find($orderId);

        $this->assertSame($user->id, $order->user_id);
    }

    public function test_checkout_stores_customer_information(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $payload = $this->payload($variant);
        $payload['customer_email'] = 'comprador@tienda.co';
        $payload['customer_name']  = 'María López';
        $payload['customer_phone'] = '3157654321';

        $this->actingAs($user)->postJson('/api/checkout', $payload);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'comprador@tienda.co',
            'customer_name'  => 'María López',
            'customer_phone' => '3157654321',
        ]);
    }

    public function test_checkout_applies_discount_and_sets_correct_totals(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();
        $rule = DiscountRule::factory()->create(['min_quantity' => 1, 'max_quantity' => null]);

        $payload = $this->payload($variant, quantity: 2, unitPrice: 50000.00);
        $payload['items'][0]['discount_rule_id']       = $rule->id;
        $payload['items'][0]['discount_percentage']    = 10;
        $payload['items'][0]['discounted_unit_price']  = 45000.00;
        $payload['items'][0]['discounted_total_price'] = 90000.00;

        $response = $this->actingAs($user)->postJson('/api/checkout', $payload);

        $response->assertStatus(201);

        $order = Order::where('user_id', $user->id)->first();
        $this->assertEquals(100000.00, $order->subtotal_original);
        $this->assertEquals(90000.00, $order->subtotal_discounted);
        $this->assertEquals(90000.00, $order->total);
    }

    public function test_payment_config_amount_matches_order_total_in_cents(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $payload = $this->payload($variant, quantity: 1, unitPrice: 75000.00);
        $payload['items'][0]['total_price'] = 75000.00;

        $response = $this->actingAs($user)->postJson('/api/checkout', $payload);

        $amountInCents = $response->json('data.payment.amount_in_cents');
        $total         = $response->json('data.order.total');

        $this->assertSame((int) round($total * 100), $amountInCents);
    }

    public function test_payment_config_reference_matches_order_number(): void
    {
        [$user, $variant] = $this->setupCheckoutDependencies();

        $response = $this->actingAs($user)->postJson('/api/checkout', $this->payload($variant));

        $orderNumber = $response->json('data.order.order_number');
        $reference   = $response->json('data.payment.reference');

        $this->assertSame($orderNumber, $reference);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * @return array{User, ProductVariant, Inventory}
     */
    private function setupCheckoutDependencies(int $stock = 20): array
    {
        $user      = User::factory()->create();
        $variant   = ProductVariant::factory()->create();
        $inventory = Inventory::factory()->withStock($stock)->create([
            'product_variant_id' => $variant->id,
        ]);

        return [$user, $variant, $inventory];
    }

    /**
     * Build a basic valid checkout payload.
     */
    private function payload(
        ProductVariant $variant,
        int $quantity     = 2,
        float $unitPrice  = 50000.00,
        string $omit      = ''
    ): array {
        $data = [
            'customer_email' => 'cliente@test.com',
            'customer_name'  => 'Test Customer',
            'customer_phone' => '3001111111',
            'items'          => [[
                'product_variant_id' => $variant->id,
                'quantity'           => $quantity,
                'unit_price'         => $unitPrice,
                'total_price'        => $unitPrice * $quantity,
            ]],
        ];

        if ($omit) {
            unset($data[$omit]);
        }

        return $data;
    }
}
