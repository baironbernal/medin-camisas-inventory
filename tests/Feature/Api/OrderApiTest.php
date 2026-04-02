<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for GET /api/orders and GET /api/orders/{order}.
 */
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    // ── GET /orders ──────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_list_orders(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->forUser($user)->create();

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.total', 3);
    }

    public function test_user_only_sees_their_own_orders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Order::factory()->count(3)->forUser($userA)->create();
        Order::factory()->count(5)->forUser($userB)->create();

        $response = $this->actingAs($userA)->getJson('/api/orders');

        $response->assertStatus(200);
        $this->assertSame(3, $response->json('data.total'));
    }

    public function test_orders_list_is_paginated_with_15_per_page(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(20)->forUser($user)->create();

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data.data'));
    }

    public function test_orders_are_sorted_newest_first(): void
    {
        $user   = User::factory()->create();
        $older  = Order::factory()->forUser($user)->create(['created_at' => now()->subDays(2)]);
        $newer  = Order::factory()->forUser($user)->create(['created_at' => now()]);

        $items = $this->actingAs($user)->getJson('/api/orders')->json('data.data');

        $this->assertSame($newer->id, $items[0]['id']);
        $this->assertSame($older->id, $items[1]['id']);
    }

    public function test_orders_list_returns_empty_when_user_has_no_orders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/orders');

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('data.total'));
    }

    // ── GET /orders/{order} ──────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_view_order_detail(): void
    {
        $order = Order::factory()->create();
        $this->getJson("/api/orders/{$order->id}")->assertStatus(401);
    }

    public function test_authenticated_user_can_view_order_detail(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'id', 'order_number', 'status', 'subtotal_original',
                    'subtotal_discounted', 'subtotal', 'tax', 'shipping_cost',
                    'total', 'currency', 'customer_email', 'customer_name',
                    'customer_phone', 'created_at', 'items',
                ],
            ]);
    }

    public function test_order_detail_returns_correct_data(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->forUser($user)->create([
            'customer_email' => 'detail@test.com',
            'total'          => 150000.00,
            'currency'       => 'COP',
        ]);

        $response = $this->actingAs($user)->getJson("/api/orders/{$order->id}");

        $response->assertJsonPath('data.customer_email', 'detail@test.com')
            ->assertJsonPath('data.currency', 'COP');
    }

    public function test_order_detail_includes_items_with_variant_info(): void
    {
        $user    = User::factory()->create();
        $order   = Order::factory()->forUser($user)->create();
        $variant = ProductVariant::factory()->create();

        OrderItem::factory()->create([
            'order_id'            => $order->id,
            'product_variant_id'  => $variant->id,
            'product_name'        => 'Camiseta Roja',
            'variant_sku'         => 'CMR-M-RED',
            'quantity'            => 3,
            'unit_price'          => 35000.00,
        ]);

        $response = $this->actingAs($user)->getJson("/api/orders/{$order->id}");

        $item = $response->json('data.items.0');

        $this->assertSame('Camiseta Roja', $item['product_name']);
        $this->assertSame('CMR-M-RED', $item['variant_sku']);
        $this->assertSame(3, $item['quantity']);
    }

    public function test_requesting_nonexistent_order_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/orders/99999')->assertStatus(404);
    }
}
