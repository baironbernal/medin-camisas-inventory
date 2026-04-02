<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for POST /api/webhooks/wompi.
 *
 * Covers: signature verification, status transitions (APPROVED / DECLINED /
 * VOIDED / ERROR), idempotency, and inventory deduction on approval.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $eventsKey = 'test_events_key_for_webhook';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.wompi.events_key' => $this->eventsKey]);
    }

    // ── Signature verification ───────────────────────────────────────────────

    public function test_webhook_with_invalid_signature_returns_401(): void
    {
        $order   = Order::factory()->pending()->create();
        $payload = $this->buildPayload($order->order_number, 'APPROVED');

        // Tamper with checksum
        $payload['signature']['checksum'] = 'totally_fake_checksum';

        $this->postJson('/api/webhooks/wompi', $payload)->assertStatus(401);
    }

    public function test_webhook_with_missing_signature_returns_401(): void
    {
        $order = Order::factory()->pending()->create();

        $payload = $this->buildPayload($order->order_number, 'APPROVED');
        unset($payload['signature']);

        $this->postJson('/api/webhooks/wompi', $payload)->assertStatus(401);
    }

    // ── Event filtering ──────────────────────────────────────────────────────

    public function test_non_transaction_updated_event_is_ignored_with_200(): void
    {
        $order   = Order::factory()->pending()->create();
        $payload = $this->buildPayload($order->order_number, 'APPROVED', event: 'payment.created');

        $this->postJson('/api/webhooks/wompi', $payload)
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Event ignored']);

        // Order must be untouched
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    // ── Order lookup ─────────────────────────────────────────────────────────

    public function test_webhook_for_unknown_reference_returns_404(): void
    {
        $payload = $this->buildPayload('ORD-NONEXISTENT-000000', 'APPROVED');

        $this->postJson('/api/webhooks/wompi', $payload)->assertStatus(404);
    }

    // ── APPROVED ─────────────────────────────────────────────────────────────

    public function test_approved_payment_confirms_the_order(): void
    {
        $order = Order::factory()->pending()->create();
        $this->attachInventoryItem($order);

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'APPROVED', transactionId: 'TXN_001'))
            ->assertStatus(200);

        $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_approved_payment_stores_wompi_transaction_id(): void
    {
        $order = Order::factory()->pending()->create();
        $this->attachInventoryItem($order);

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'APPROVED', transactionId: 'TXN_XYZ_123'));

        $this->assertSame('TXN_XYZ_123', $order->fresh()->wompi_transaction_id);
    }

    public function test_approved_payment_deducts_stock_and_creates_sale_movement(): void
    {
        $order   = Order::factory()->pending()->create();
        [$variant, $inventory] = $this->attachInventoryItem($order, quantity: 3, stock: 20);

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'APPROVED'));

        $this->assertSame(17, $inventory->fresh()->quantity_available);
        $this->assertDatabaseHas('movements', [
            'product_variant_id' => $variant->id,
            'type'               => Movement::TYPE_SALE,
            'quantity'           => 3,
        ]);
    }

    // ── DECLINED / VOIDED / ERROR ────────────────────────────────────────────

    public function test_declined_payment_cancels_the_order(): void
    {
        $order = Order::factory()->pending()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'DECLINED'))
            ->assertStatus(200);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_voided_payment_cancels_the_order(): void
    {
        $order = Order::factory()->pending()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'VOIDED'));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_errored_payment_cancels_the_order(): void
    {
        $order = Order::factory()->pending()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'ERROR'));

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_declined_payment_stores_wompi_transaction_id(): void
    {
        $order = Order::factory()->pending()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'DECLINED', transactionId: 'TXN_DECLINED'));

        $this->assertSame('TXN_DECLINED', $order->fresh()->wompi_transaction_id);
    }

    public function test_declined_payment_does_not_deduct_stock(): void
    {
        $order = Order::factory()->pending()->create();
        [$variant, $inventory] = $this->attachInventoryItem($order, quantity: 2, stock: 10);

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'DECLINED'));

        $this->assertSame(10, $inventory->fresh()->quantity_available);
        $this->assertDatabaseCount('movements', 0);
    }

    // ── Idempotency ──────────────────────────────────────────────────────────

    public function test_webhook_is_idempotent_for_already_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'APPROVED'))
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Order already processed']);

        // Status must remain confirmed (not re-processed)
        $this->assertSame(Order::STATUS_CONFIRMED, $order->fresh()->status);
    }

    public function test_webhook_is_idempotent_for_already_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'DECLINED'))
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Order already processed']);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    // ── PENDING transaction status ────────────────────────────────────────────

    public function test_pending_transaction_status_leaves_order_unchanged(): void
    {
        $order = Order::factory()->pending()->create();

        $this->postJson('/api/webhooks/wompi', $this->buildPayload($order->order_number, 'PENDING'))
            ->assertStatus(200);

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build a signed Wompi webhook payload.
     */
    private function buildPayload(
        string $reference,
        string $status,
        string $transactionId = 'TXN_TEST_001',
        string $event         = 'transaction.updated',
        int $amountInCents    = 5000000,
    ): array {
        $timestamp  = (string) time();
        $properties = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];

        $data = [
            'transaction' => [
                'id'              => $transactionId,
                'status'          => $status,
                'amount_in_cents' => $amountInCents,
                'reference'       => $reference,
            ],
        ];

        $concatenated = '';
        foreach ($properties as $property) {
            $concatenated .= data_get($data, $property);
        }
        $concatenated .= $timestamp;
        $concatenated .= $this->eventsKey;

        return [
            'event'     => $event,
            'data'      => $data,
            'signature' => [
                'properties' => $properties,
                'checksum'   => hash('sha256', $concatenated),
            ],
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Attach a product variant inventory and order item to an order.
     *
     * @return array{ProductVariant, Inventory}
     */
    private function attachInventoryItem(
        Order $order,
        int $quantity = 2,
        int $stock    = 10,
    ): array {
        $variant   = ProductVariant::factory()->create();
        $inventory = Inventory::factory()->withStock($stock)->create([
            'product_variant_id' => $variant->id,
        ]);

        OrderItem::factory()->create([
            'order_id'               => $order->id,
            'product_variant_id'     => $variant->id,
            'quantity'               => $quantity,
            'discounted_unit_price'  => 30000,
            'discounted_total_price' => 30000 * $quantity,
        ]);

        return [$variant, $inventory];
    }
}
