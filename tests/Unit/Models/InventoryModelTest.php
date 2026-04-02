<?php

namespace Tests\Unit\Models;

use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Inventory model's computed attributes and scopes.
 */
class InventoryModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Computed attributes ──────────────────────────────────────────────────

    public function test_total_quantity_sums_all_quantity_states(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available'  => 30,
            'quantity_reserved'   => 10,
            'quantity_in_transit' => 5,
        ]);

        $this->assertSame(45, $inventory->total_quantity);
    }

    public function test_needs_restock_is_true_when_available_is_at_or_below_reorder_point(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 5,
            'reorder_point'      => 10,
        ]);

        $this->assertTrue($inventory->needs_restock);
    }

    public function test_needs_restock_is_false_when_available_is_above_reorder_point(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 20,
            'reorder_point'      => 10,
        ]);

        $this->assertFalse($inventory->needs_restock);
    }

    public function test_is_low_stock_is_true_when_available_is_at_or_below_min_quantity(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 3,
            'min_quantity'       => 5,
        ]);

        $this->assertTrue($inventory->is_low_stock);
    }

    public function test_is_low_stock_is_false_when_no_min_quantity_set(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 1,
            'min_quantity'       => null,
        ]);

        $this->assertFalse($inventory->is_low_stock);
    }

    public function test_is_overstocked_is_true_when_available_exceeds_max_quantity(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 110,
            'max_quantity'       => 100,
        ]);

        $this->assertTrue($inventory->is_overstocked);
    }

    public function test_is_overstocked_is_false_when_no_max_quantity_set(): void
    {
        $inventory = Inventory::factory()->make([
            'quantity_available' => 9999,
            'max_quantity'       => null,
        ]);

        $this->assertFalse($inventory->is_overstocked);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function test_low_stock_scope_returns_only_low_stock_records(): void
    {
        // Low stock: qty <= min_quantity
        Inventory::factory()->create(['quantity_available' => 3, 'min_quantity' => 5]);
        Inventory::factory()->create(['quantity_available' => 5, 'min_quantity' => 5]); // edge: exactly at min

        // Not low stock
        Inventory::factory()->create(['quantity_available' => 20, 'min_quantity' => 5]);
        Inventory::factory()->create(['quantity_available' => 10, 'min_quantity' => null]);

        $results = Inventory::lowStock()->get();

        $this->assertCount(2, $results);
    }

    public function test_needs_restock_scope_returns_inventories_at_or_below_reorder_point(): void
    {
        Inventory::factory()->create(['quantity_available' => 5,  'reorder_point' => 10]);
        Inventory::factory()->create(['quantity_available' => 10, 'reorder_point' => 10]); // edge: exactly at point

        // Should not appear
        Inventory::factory()->create(['quantity_available' => 15, 'reorder_point' => 10]);
        Inventory::factory()->create(['quantity_available' => 5,  'reorder_point' => 0]);  // reorder_point = 0

        $results = Inventory::needsRestock()->get();

        $this->assertCount(2, $results);
    }
}
