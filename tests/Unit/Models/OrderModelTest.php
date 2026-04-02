<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use Database\Factories\OrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Order model's business-logic methods and scopes.
 * These do not test HTTP endpoints — only model behaviour.
 */
class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    // ── generateOrderNumber ──────────────────────────────────────────────────

    public function test_it_generates_an_order_number_with_correct_format(): void
    {
        $number = Order::generateOrderNumber();

        $this->assertMatchesRegularExpression('/^ORD-\d{8}-[A-Z0-9]{6}$/', $number);
    }

    public function test_it_generates_unique_order_numbers(): void
    {
        $numbers = array_map(fn () => Order::generateOrderNumber(), range(1, 20));

        $this->assertCount(20, array_unique($numbers));
    }

    public function test_it_retries_if_order_number_already_exists(): void
    {
        // Force a collision by pre-creating an order and verifying the
        // method still returns something unique.
        $order = Order::factory()->create(['order_number' => 'ORD-20260401-AAAAAA']);

        $number = Order::generateOrderNumber();

        $this->assertNotSame('ORD-20260401-AAAAAA', $number);
    }

    // ── Status transition helpers ────────────────────────────────────────────

    public function test_pending_order_can_be_cancelled(): void
    {
        $order = Order::factory()->pending()->make();
        $this->assertTrue($order->canBeCancelled());
    }

    public function test_confirmed_order_can_be_cancelled(): void
    {
        $order = Order::factory()->confirmed()->make();
        $this->assertTrue($order->canBeCancelled());
    }

    public function test_completed_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->completed()->make();
        $this->assertFalse($order->canBeCancelled());
    }

    public function test_processing_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->processing()->make();
        $this->assertFalse($order->canBeCancelled());
    }

    public function test_pending_order_can_be_confirmed(): void
    {
        $order = Order::factory()->pending()->make();
        $this->assertTrue($order->canBeConfirmed());
    }

    public function test_confirmed_order_cannot_be_confirmed_again(): void
    {
        $order = Order::factory()->confirmed()->make();
        $this->assertFalse($order->canBeConfirmed());
    }

    public function test_confirmed_order_can_be_completed(): void
    {
        $order = Order::factory()->confirmed()->make();
        $this->assertTrue($order->canBeCompleted());
    }

    public function test_processing_order_can_be_completed(): void
    {
        $order = Order::factory()->processing()->make();
        $this->assertTrue($order->canBeCompleted());
    }

    public function test_pending_order_cannot_be_completed(): void
    {
        $order = Order::factory()->pending()->make();
        $this->assertFalse($order->canBeCompleted());
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function test_pending_scope_only_returns_pending_orders(): void
    {
        Order::factory()->count(3)->pending()->create();
        Order::factory()->count(2)->confirmed()->create();

        $results = Order::pending()->get();

        $this->assertCount(3, $results);
        $results->each(fn ($o) => $this->assertSame(Order::STATUS_PENDING, $o->status));
    }

    public function test_completed_scope_only_returns_completed_orders(): void
    {
        Order::factory()->count(2)->completed()->create();
        Order::factory()->count(3)->pending()->create();

        $results = Order::completed()->get();

        $this->assertCount(2, $results);
        $results->each(fn ($o) => $this->assertSame(Order::STATUS_COMPLETED, $o->status));
    }

    public function test_for_user_scope_filters_by_user_id(): void
    {
        $userA = \App\Models\User::factory()->create();
        $userB = \App\Models\User::factory()->create();

        Order::factory()->count(3)->create(['user_id' => $userA->id]);
        Order::factory()->count(2)->create(['user_id' => $userB->id]);

        $results = Order::forUser($userA->id)->get();

        $this->assertCount(3, $results);
        $results->each(fn ($o) => $this->assertSame($userA->id, $o->user_id));
    }
}
