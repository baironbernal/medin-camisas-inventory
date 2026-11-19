<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Meta Purchase is tied to the order system (paid_at), not to Wompi:
 * transfer / cash / cash-on-delivery orders are reported too, once, when paid.
 */
class OrderPurchaseTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.meta.pixel_id'     => 'PIXEL',
            'services.meta.access_token' => 'TOKEN',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
    }

    public function test_transfer_order_is_paid_and_reported_when_confirmed(): void
    {
        $order = $this->whatsappOrder();

        $order->update(['status' => Order::STATUS_CONFIRMED, 'payment_method' => Order::PAYMENT_TRANSFER]);
        $this->app->terminate();

        $order->refresh();
        $this->assertNotNull($order->paid_at);
        $this->assertNotNull($order->meta_purchase_sent_at);
        $this->assertPurchaseSent($order, 'transferencia');
    }

    public function test_cash_on_delivery_is_reported_only_when_completed(): void
    {
        $order = $this->whatsappOrder();

        $order->update(['status' => Order::STATUS_CONFIRMED, 'payment_method' => Order::PAYMENT_CASH_ON_DELIVERY]);
        $this->app->terminate();

        $this->assertNull($order->fresh()->paid_at);
        Http::assertNothingSent();

        $order->update(['status' => Order::STATUS_COMPLETED]);
        $this->app->terminate();

        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertPurchaseSent($order, 'contraentrega');
    }

    public function test_purchase_is_sent_only_once(): void
    {
        $order = $this->whatsappOrder();

        $order->update(['status' => Order::STATUS_CONFIRMED, 'payment_method' => Order::PAYMENT_CASH]);
        $this->app->terminate();
        $order->refresh()->update(['status' => Order::STATUS_COMPLETED]);
        $this->app->terminate();

        Http::assertSentCount(1);
    }

    public function test_admin_panel_orders_are_not_reported(): void
    {
        $order = $this->whatsappOrder(['source' => Order::SOURCE_ADMIN]);

        $order->update(['status' => Order::STATUS_CONFIRMED, 'payment_method' => Order::PAYMENT_TRANSFER]);
        $this->app->terminate();

        $this->assertNotNull($order->fresh()->paid_at);
        Http::assertNothingSent();
    }

    public function test_whatsapp_order_creation_sends_order_placed_not_purchase(): void
    {
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->withStock(10)->create(['product_variant_id' => $variant->id]);

        $response = $this->postJson('/api/whatsapp-order', [
            'customer_name'  => 'Ana Pérez',
            'customer_phone' => '3001234567',
            'items'          => [['product_variant_id' => $variant->id, 'quantity' => 2]],
            'meta'           => ['fbp' => 'fb.1.123.456'],
        ])->assertCreated();

        $order = Order::where('order_number', $response->json('data.order.order_number'))->first();
        $this->assertSame(Order::SOURCE_WHATSAPP, $order->source);
        $this->assertNull($order->paid_at);
        $this->assertSame('fb.1.123.456', $order->meta_tracking['fbp']);

        Http::assertSent(function (Request $request) use ($order) {
            $event = $request['data'][0];

            return $event['event_name'] === 'OrderPlaced'
                && $event['event_id'] === $order->order_number . '-placed'
                && $event['custom_data']['payment_status'] === 'pending';
        });
        Http::assertSentCount(1);
    }

    private function whatsappOrder(array $attributes = []): Order
    {
        $order = Order::factory()->pending()->create($attributes + ['source' => Order::SOURCE_WHATSAPP]);
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->withStock(10)->create(['product_variant_id' => $variant->id]);
        OrderItem::factory()->create([
            'order_id'           => $order->id,
            'product_variant_id' => $variant->id,
            'variant_sku'        => $variant->sku,
            'quantity'           => 1,
        ]);

        return $order;
    }

    private function assertPurchaseSent(Order $order, string $paymentMethod): void
    {
        Http::assertSent(function (Request $request) use ($order, $paymentMethod) {
            $event = $request['data'][0];

            return $event['event_name'] === 'Purchase'
                && $event['event_id'] === $order->order_number
                && $event['custom_data']['payment_method'] === $paymentMethod
                && $event['custom_data']['payment_status'] === 'paid';
        });
    }
}
