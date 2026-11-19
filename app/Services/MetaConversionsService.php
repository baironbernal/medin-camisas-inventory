<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Conversions API — sends server-side events to Meta.
 *
 * Tied to the order system, not to the payment gateway:
 *  - OrderPlaced: every order generated on the site (Wompi or WhatsApp), still unpaid.
 *  - Purchase: when the order is actually paid, whatever the method (Wompi,
 *    transferencia, efectivo, contraentrega) — see OrderObserver / Order::paid_at.
 * Both carry payment_method, payment_status and order_source in custom_data.
 * Purchase event_id = order number, the same one the browser Pixel uses, so
 * Meta deduplicates and counts the purchase once.
 *
 * Does nothing until META_PIXEL_ID and META_ACCESS_TOKEN are configured.
 */
class MetaConversionsService
{
    protected const API_VERSION = 'v21.0';

    public function isConfigured(): bool
    {
        return filled(config('services.meta.pixel_id')) && filled(config('services.meta.access_token'));
    }

    /**
     * Purchase — the sale was actually paid (Wompi approved, transfer/cash
     * confirmed, or cash on delivery collected). Sent once per order.
     */
    public function sendPurchase(?Order $order): void
    {
        if (! $order || ! $this->isConfigured()) {
            return;
        }

        // Re-checked here: the status update may have rolled back, or another
        // request may have reported it already.
        if (! $order->isPaid() || ! $order->isFromWebsite() || $order->meta_purchase_sent_at !== null) {
            return;
        }

        // event_id = order number, the same one the browser Pixel uses on Wompi
        // payments, so Meta deduplicates and counts the purchase once.
        $sent = $this->send([$this->buildEvent($order, 'Purchase', $order->order_number)]);

        if ($sent) {
            $order->forceFill(['meta_purchase_sent_at' => now()])->saveQuietly();
        }
    }

    /**
     * OrderPlaced (custom event) — an order was generated on the site but
     * isn't paid yet. Lets Meta tell generated orders apart from real sales.
     */
    public function sendOrderPlaced(?Order $order): void
    {
        if (! $order || ! $this->isConfigured() || ! $order->isFromWebsite()) {
            return;
        }

        $this->send([$this->buildEvent($order, 'OrderPlaced', $order->order_number . '-placed')]);
    }

    protected function buildEvent(Order $order, string $eventName, string $eventId): array
    {
        // productVariant withTrashed: a variant deleted after the sale still has its product_id
        $order->loadMissing(['items.productVariant' => fn ($q) => $q->withTrashed()]);
        $tracking = $order->meta_tracking ?? [];

        $userData = array_filter([
            'em'                => $this->hash($order->customer_email),
            'ph'                => $this->hash($this->normalizePhone($order->customer_phone)),
            'fn'                => $this->hash($this->firstName($order->customer_name)),
            'external_id'       => $order->user_id ? $this->hash((string) $order->user_id) : null,
            'fbp'               => $tracking['fbp'] ?? null,
            'fbc'               => $tracking['fbc'] ?? null,
            'client_ip_address' => $tracking['ip'] ?? null,
            'client_user_agent' => $tracking['user_agent'] ?? null,
        ]);

        return [
            'event_name'       => $eventName,
            'event_time'       => now()->timestamp,
            'event_id'         => $eventId,
            'action_source'    => 'website',
            'event_source_url' => $tracking['source_url'] ?? config('app.url'),
            'user_data'        => $userData,
            'custom_data'      => [
                'currency'       => $order->currency ?? 'COP',
                'value'          => (float) $order->total,
                'content_type'   => 'product',
                // Product IDs, matching the browser Pixel events (ViewContent, AddToCart...)
                'content_ids'    => $this->contents($order)->pluck('id')->all(),
                'content_name'   => $order->items->pluck('product_name')->filter()->unique()->implode(', '),
                'contents'       => $this->contents($order)->all(),
                'num_items'      => $order->items->sum('quantity'),
                'order_id'       => $order->order_number,
                'payment_method' => $order->payment_method ?? 'por_definir',
                'payment_status' => $order->isPaid() ? 'paid' : 'pending',
                'order_source'   => $order->source,
            ],
        ];
    }

    /** One entry per product, quantities summed across its variants. */
    protected function contents(Order $order)
    {
        return $order->items
            ->groupBy(fn ($item) => (string) ($item->productVariant?->product_id ?? $item->variant_sku))
            ->map(fn ($items, $id) => [
                'id'         => (string) $id,
                'quantity'   => $items->sum('quantity'),
                'item_price' => (float) $items->first()->discounted_unit_price,
            ])
            ->values();
    }

    protected function send(array $events): bool
    {
        $payload = ['data' => $events];

        if ($testCode = config('services.meta.test_event_code')) {
            $payload['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(5)
                ->post(sprintf(
                    'https://graph.facebook.com/%s/%s/events?access_token=%s',
                    self::API_VERSION,
                    config('services.meta.pixel_id'),
                    config('services.meta.access_token'),
                ), $payload);

            if ($response->failed()) {
                Log::warning('Meta CAPI: request failed', [
                    'status' => $response->status(),
                    'body'   => $response->json() ?? $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Tracking must never break the payment flow
            Log::warning('Meta CAPI: exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Meta requires user data lowercased, trimmed and SHA-256 hashed. */
    protected function hash(?string $value): ?string
    {
        $value = $value !== null ? mb_strtolower(trim($value)) : '';

        return $value === '' ? null : hash('sha256', $value);
    }

    /** Digits only, with Colombia's country code (57) for 10-digit mobiles. */
    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return strlen($digits) === 10 ? '57' . $digits : $digits;
    }

    protected function firstName(?string $name): ?string
    {
        return $name ? strtok(trim($name), ' ') ?: null : null;
    }
}
