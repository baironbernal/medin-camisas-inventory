<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration-style unit tests for InventoryService.
 * Validates stock deduction and movement record creation after payment confirmation.
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    // ── Happy path ───────────────────────────────────────────────────────────

    public function test_it_decrements_quantity_available_for_each_item(): void
    {
        [$order, $variant, $inventory] = $this->createConfirmedOrderWithStock(
            quantityOrdered: 3,
            stockBefore: 10
        );

        $this->service->deductStockForOrder($order);

        $this->assertSame(7, $inventory->fresh()->quantity_available);
    }

    public function test_it_creates_a_sale_movement_for_each_item(): void
    {
        [$order, $variant, $inventory] = $this->createConfirmedOrderWithStock(
            quantityOrdered: 2,
            stockBefore: 20
        );

        $this->service->deductStockForOrder($order);

        $movement = Movement::where('product_variant_id', $variant->id)->first();

        $this->assertNotNull($movement);
        $this->assertSame(Movement::TYPE_SALE, $movement->type);
        $this->assertSame(2, $movement->quantity);
        $this->assertSame($order->order_number, $movement->reference_document);
        $this->assertSame($inventory->id, $movement->inventory_id);
        $this->assertSame($inventory->store_id, $movement->store_id);
    }

    public function test_movement_records_correct_before_and_after_quantities(): void
    {
        [$order, $variant, $inventory] = $this->createConfirmedOrderWithStock(
            quantityOrdered: 4,
            stockBefore: 15
        );

        $this->service->deductStockForOrder($order);

        $movement = Movement::where('product_variant_id', $variant->id)->first();

        $this->assertSame(15, $movement->quantity_before);
        $this->assertSame(11, $movement->quantity_after);
    }

    public function test_movement_stores_discounted_prices_as_cost(): void
    {
        [$order, $variant, $inventory] = $this->createConfirmedOrderWithStock(
            quantityOrdered: 1,
            stockBefore: 5,
            discountedUnitPrice: 45000.00,
            discountedTotalPrice: 45000.00,
        );

        $this->service->deductStockForOrder($order);

        $movement = Movement::where('product_variant_id', $variant->id)->first();

        $this->assertEquals(45000.00, $movement->unit_cost);
        $this->assertEquals(45000.00, $movement->total_cost);
    }

    public function test_it_creates_movements_for_all_items_in_order(): void
    {
        $order    = Order::factory()->confirmed()->create();
        $variantA = ProductVariant::factory()->create();
        $variantB = ProductVariant::factory()->create();
        $invA     = Inventory::factory()->withStock(20)->create(['product_variant_id' => $variantA->id]);
        $invB     = Inventory::factory()->withStock(20)->create(['product_variant_id' => $variantB->id]);

        OrderItem::factory()->create([
            'order_id'               => $order->id,
            'product_variant_id'     => $variantA->id,
            'quantity'               => 3,
            'discounted_unit_price'  => 30000,
            'discounted_total_price' => 90000,
        ]);
        OrderItem::factory()->create([
            'order_id'               => $order->id,
            'product_variant_id'     => $variantB->id,
            'quantity'               => 5,
            'discounted_unit_price'  => 20000,
            'discounted_total_price' => 100000,
        ]);

        $this->service->deductStockForOrder($order);

        $this->assertSame(17, $invA->fresh()->quantity_available);
        $this->assertSame(15, $invB->fresh()->quantity_available);
        $this->assertDatabaseCount('movements', 2);
    }

    public function test_movement_quantity_after_is_floored_at_zero_when_oversold(): void
    {
        // Stock = 2, order quantity = 5 → quantity_after in movement = max(0, 2-5) = 0
        // Note: `decrement()` on the inventory record itself will go to -3;
        // only the movement's quantity_after is protected by max(0, …).
        [$order, $variant, $inventory] = $this->createConfirmedOrderWithStock(
            quantityOrdered: 5,
            stockBefore: 2
        );

        $this->service->deductStockForOrder($order);

        $movement = Movement::where('product_variant_id', $variant->id)->first();
        $this->assertSame(0, $movement->quantity_after);
        $this->assertSame(2, $movement->quantity_before);
    }

    // ── Error paths ──────────────────────────────────────────────────────────

    public function test_it_throws_when_inventory_record_does_not_exist(): void
    {
        $order   = Order::factory()->confirmed()->create();
        $variant = ProductVariant::factory()->create(); // No Inventory created

        OrderItem::factory()->create([
            'order_id'           => $order->id,
            'product_variant_id' => $variant->id,
            'quantity'           => 1,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No hay inventario');

        $this->service->deductStockForOrder($order);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Creates a confirmed order with one item and a corresponding inventory record.
     *
     * @return array{Order, ProductVariant, Inventory}
     */
    private function createConfirmedOrderWithStock(
        int $quantityOrdered,
        int $stockBefore,
        float $discountedUnitPrice  = 30000.00,
        float $discountedTotalPrice = 30000.00,
    ): array {
        $order   = Order::factory()->confirmed()->create();
        $variant = ProductVariant::factory()->create();
        $inv     = Inventory::factory()->withStock($stockBefore)->create([
            'product_variant_id' => $variant->id,
        ]);

        OrderItem::factory()->create([
            'order_id'               => $order->id,
            'product_variant_id'     => $variant->id,
            'quantity'               => $quantityOrdered,
            'discounted_unit_price'  => $discountedUnitPrice,
            'discounted_total_price' => $discountedTotalPrice * $quantityOrdered,
        ]);

        return [$order, $variant, $inv];
    }
}
