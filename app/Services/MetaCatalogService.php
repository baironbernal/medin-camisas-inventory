<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Meta Catalog Batch API — keeps the Meta catalog in sync with the store.
 *
 * Each catalog item's id is the product ID, the same one the Pixel and the
 * Conversions API send as content_ids, so Meta can match events to products.
 *
 * Does nothing until META_CATALOG_ID and META_CATALOG_TOKEN are configured.
 */
class MetaCatalogService
{
    protected const API_VERSION = 'v21.0';

    protected const BATCH_SIZE = 1000;

    public function isConfigured(): bool
    {
        return filled(config('services.meta.catalog_id')) && filled(config('services.meta.catalog_token'));
    }

    /** Sends every product to Meta and returns how many were sent. */
    public function sync(): int
    {
        $requests = Product::withTrashed()
            ->with('variants.inventories')
            ->get()
            ->map(fn (Product $product) => $this->toRequest($product));

        foreach ($requests->chunk(self::BATCH_SIZE) as $batch) {
            $this->send($batch->values()->all());
        }

        return $requests->count();
    }

    /** Products the store doesn't show (inactive, deleted or without photos) are removed. */
    protected function toRequest(Product $product): array
    {
        $image = $this->image($product);

        if (! $product->is_active || $product->trashed() || ! $image) {
            return ['method' => 'DELETE', 'data' => ['id' => (string) $product->id]];
        }

        $stock = $product->variants->sum(fn ($variant) => $variant->inventories->sum('quantity_available'));

        return [
            'method' => 'UPDATE',
            'data'   => [
                'id'           => (string) $product->id,
                'title'        => mb_substr(trim($product->name), 0, 200),
                'description'  => mb_substr(trim($product->description ?: $product->name), 0, 9999),
                'availability' => $stock > 0 ? 'in stock' : 'out of stock',
                'condition'    => 'new',
                'price'        => number_format((float) $product->base_price, 0, '.', '') . ' COP',
                'link'         => rtrim(config('services.meta.store_url'), '/') . '/producto/' . $product->slug,
                'image_link'   => Storage::disk('public')->url($image),
                'brand'        => $product->brand ?: config('company.company_name'),
            ],
        ];
    }

    /** First product photo, falling back to the photos of its variants. */
    protected function image(Product $product): ?string
    {
        return collect($product->images)
            ->merge($product->variants->pluck('images')->flatten())
            ->filter()
            ->first();
    }

    protected function send(array $requests): void
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post(sprintf(
                'https://graph.facebook.com/%s/%s/items_batch',
                self::API_VERSION,
                config('services.meta.catalog_id'),
            ), [
                'access_token' => config('services.meta.catalog_token'),
                'item_type'    => 'PRODUCT_ITEM',
                'requests'     => json_encode($requests),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Meta catalog sync failed: ' . $response->body());
        }

        if ($invalid = collect($response->json('validation_status'))->filter(fn ($item) => ! empty($item['errors']))->values()->all()) {
            Log::warning('Meta catalog: items rejected', ['items' => $invalid]);
        }
    }
}
