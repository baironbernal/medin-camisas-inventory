<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration-style unit tests for OrderService.
 * Uses a real SQLite in-memory database to verify the full order creation flow.
 */
class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
    }

    private function makeCustomerData(array $overrides = []): array
    {
        return array_merge([
            'customer_email' => 'test@example.com',
            'customer_name'  => 'Juan Pérez',
            'customer_phone' => '3001234567',
            'notes'          => null,
        ], $overrides);
    }

    // ── Happy path ───────────────────────────────────────────────────────────

    public function test_it_creates_an_order_with_correct_status_and_currency(): void
    {
        $variant   = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 10]);

        $items = [[
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
            'unit_price'         => 50000.00,
            'total_price'        => 100000.00,
        ]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('COP', $order->currency);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_it_stores_customer_data_on_the_order(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 5]);

        $customerData = $this->makeCustomerData([
            'customer_email' => 'cliente@medincamisas.co',
            'customer_name'  => 'Ana García',
            'customer_phone' => '3109876543',
            'notes'          => 'Entregar en horario de mañana',
        ]);

        $items = [[
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
            'unit_price'         => 35000.00,
            'total_price'        => 35000.00,
        ]];

        $order = $this->service->createOrderFromItems($items, $customerData);

        $this->assertSame('cliente@medincamisas.co', $order->customer_email);
        $this->assertSame('Ana García', $order->customer_name);
        $this->assertSame('3109876543', $order->customer_phone);
        $this->assertSame('Entregar en horario de mañana', $order->notes);
    }

    public function test_it_calculates_totals_correctly_without_discounts(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 20]);

        $items = [[
            'product_variant_id' => $variant->id,
            'quantity'           => 3,
            'unit_price'         => 40000.00,
            'total_price'        => 120000.00,
        ]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        $this->assertEquals(120000.00, $order->subtotal_original);
        $this->assertEquals(120000.00, $order->subtotal_discounted);
        $this->assertEquals(120000.00, $order->total);
        $this->assertSame(0, (int) $order->tax);
        $this->assertSame(0, (int) $order->shipping_cost);
    }

    public function test_it_applies_discount_percentage_to_item(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 20]);

        // 10% discount on a 50000 unit price → 45000 per unit
        $items = [[
            'product_variant_id'     => $variant->id,
            'quantity'               => 2,
            'unit_price'             => 50000.00,
            'total_price'            => 100000.00,
            'discount_percentage'    => 10,
            'discounted_unit_price'  => 45000.00,
            'discounted_total_price' => 90000.00,
        ]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        $this->assertEquals(100000.00, $order->subtotal_original);
        $this->assertEquals(90000.00, $order->subtotal_discounted);
        $this->assertEquals(90000.00, $order->total);
    }

    public function test_it_creates_order_items_with_correct_data(): void
    {
        $variant = ProductVariant::factory()->withPrice(30000)->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 10]);

        $items = [[
            'product_variant_id'     => $variant->id,
            'quantity'               => 4,
            'unit_price'             => 30000.00,
            'total_price'            => 120000.00,
            'discount_percentage'    => 15,
            'discounted_unit_price'  => 25500.00,
            'discounted_total_price' => 102000.00,
        ]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        $this->assertCount(1, $order->items);

        $item = $order->items->first();
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame(4, $item->quantity);
        $this->assertEquals(30000.00, $item->unit_price);
        $this->assertEquals(120000.00, $item->total_price);
        $this->assertEquals(25500.00, $item->discounted_unit_price);
        $this->assertEquals(102000.00, $item->discounted_total_price);
    }

    public function test_it_creates_multiple_order_items(): void
    {
        $variantA = ProductVariant::factory()->create();
        $variantB = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variantA->id, 'quantity_available' => 10]);
        Inventory::factory()->create(['product_variant_id' => $variantB->id, 'quantity_available' => 10]);

        $items = [
            ['product_variant_id' => $variantA->id, 'quantity' => 1, 'unit_price' => 20000, 'total_price' => 20000],
            ['product_variant_id' => $variantB->id, 'quantity' => 2, 'unit_price' => 30000, 'total_price' => 60000],
        ];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        $this->assertCount(2, $order->items);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertEquals(80000.00, $order->total);
    }

    public function test_it_assigns_user_id_when_provided(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 5]);

        $user  = \App\Models\User::factory()->create();
        $items = [['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData(), userId: $user->id);

        $this->assertSame($user->id, $order->user_id);
    }

    public function test_it_falls_back_to_variant_price_when_no_unit_price_provided(): void
    {
        $variant = ProductVariant::factory()->withPrice(55000)->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 5]);

        // No unit_price in payload — service should use variant->calculatePrice()
        $items = [['product_variant_id' => $variant->id, 'quantity' => 2]];

        $order = $this->service->createOrderFromItems($items, $this->makeCustomerData());

        // With no PriceRules in DB, calculatePrice() returns base_price (same as price)
        $this->assertGreaterThan(0, $order->total);
    }

    // ── Validation / error paths ─────────────────────────────────────────────

    public function test_it_throws_when_items_array_is_empty(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('carrito está vacío');

        $this->service->createOrderFromItems([], $this->makeCustomerData());
    }

    public function test_it_throws_when_variant_does_not_exist(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no encontrado');

        $items = [['product_variant_id' => 99999, 'quantity' => 1]];

        $this->service->createOrderFromItems($items, $this->makeCustomerData());
    }

    public function test_it_throws_when_variant_is_inactive(): void
    {
        $variant = ProductVariant::factory()->inactive()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 10]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no está disponible');

        $items = [['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]];

        $this->service->createOrderFromItems($items, $this->makeCustomerData());
    }

    public function test_it_throws_when_stock_is_insufficient(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_available' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $items = [['product_variant_id' => $variant->id, 'quantity' => 5, 'unit_price' => 10000, 'total_price' => 50000]];

        $this->service->createOrderFromItems($items, $this->makeCustomerData());
    }

    public function test_it_throws_when_variant_has_zero_stock(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->outOfStock()->create(['product_variant_id' => $variant->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock insuficiente');

        $items = [['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]];

        $this->service->createOrderFromItems($items, $this->makeCustomerData());
    }

    public function test_it_throws_for_inactive_variant_even_with_sufficient_stock(): void
    {
        $variant = ProductVariant::factory()->inactive()->create();
        Inventory::factory()->withStock(100)->create(['product_variant_id' => $variant->id]);

        $this->expectException(\Exception::class);

        $items = [['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000]];
        $this->service->createOrderFromItems($items, $this->makeCustomerData());
    }
}
