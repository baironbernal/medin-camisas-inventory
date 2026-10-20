<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Conversions API — sends server-side events to Meta.
 *
 * Complements the browser Pixel: ad blockers and iOS privacy settings drop many
 * browser events, so the confirmed Purchase is also sent from the server.
 * The event_id is the order number, the same one the browser Pixel uses, so
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

    public function sendPurchase(Order $order): void
    {
        if (! $this->isConfigured()) {
            return;
        }

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

        $event = [
            'event_name'       => 'Purchase',
            'event_time'       => now()->timestamp,
            'event_id'         => $order->order_number,
            'action_source'    => 'website',
            'event_source_url' => $tracking['source_url'] ?? config('app.url'),
            'user_data'        => $userData,
            'custom_data'      => [
                'currency'     => $order->currency ?? 'COP',
                'value'        => (float) $order->total,
                'content_type' => 'product',
                // Product IDs, matching the browser Pixel events (ViewContent, AddToCart...)
                'content_ids'  => $this->contents($order)->pluck('id')->all(),
                'contents'     => $this->contents($order)->all(),
                'num_items'    => $order->items->sum('quantity'),
                'order_id'     => $order->order_number,
            ],
        ];

        $this->send([$event]);
    }

    /** One entry per product, quantities summed across its variants. */
    protected function contents(Order $order)
    {
        return $order->items
            ->groupBy(fn ($item) => (string) ($item->productVariant?->product_id ?? $item->variant_sku))
            ->map(fn ($items, $id) => ['id' => (string) $id, 'quantity' => $items->sum('quantity')])
            ->values();
    }

    protected function send(array $events): void
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
            }
        } catch (\Throwable $e) {
            // Tracking must never break the payment flow
            Log::warning('Meta CAPI: exception', ['error' => $e->getMessage()]);
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
