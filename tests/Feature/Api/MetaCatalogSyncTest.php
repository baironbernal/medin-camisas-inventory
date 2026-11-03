<?php

namespace Tests\Feature\Api;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for the meta:sync-catalog command and for the product IDs the
 * storefront needs to send Pixel events that match the catalog.
 */
class MetaCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.meta.catalog_id'    => '123',
            'services.meta.catalog_token' => 'token',
            'services.meta.store_url'     => 'https://shop.test',
        ]);
    }

    private function fakeMeta(int $status = 200): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['handles' => ['abc'], 'validation_status' => []], $status)]);
    }

    private function product(array $attributes = [], int $stock = 5): Product
    {
        $product = Product::factory()->create($attributes);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'images' => ['products/a.jpg']]);
        Inventory::factory()->withStock($stock)->create(['product_variant_id' => $variant->id]);

        return $product;
    }

    private function sentRequests(): array
    {
        $sent = Http::recorded()->first()[0];

        return json_decode($sent['requests'], true);
    }

    public function test_active_product_is_sent_with_the_id_used_by_pixel_events(): void
    {
        $product = $this->product(['base_price' => 45000]);

        $this->fakeMeta();
        $this->artisan('meta:sync-catalog')->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/123/items_batch')
            && $request['item_type'] === 'PRODUCT_ITEM');

        [$item] = $this->sentRequests();
        $this->assertSame('UPDATE', $item['method']);
        $this->assertSame((string) $product->id, $item['data']['id']);
        $this->assertSame('45000 COP', $item['data']['price']);
        $this->assertSame('in stock', $item['data']['availability']);
        $this->assertSame("https://shop.test/producto/{$product->slug}", $item['data']['link']);
        $this->assertStringEndsWith('/storage/products/a.jpg', $item['data']['image_link']);
    }

    public function test_product_without_stock_is_out_of_stock(): void
    {
        $this->product(stock: 0);

        $this->fakeMeta();
        $this->artisan('meta:sync-catalog')->assertSuccessful();

        $this->assertSame('out of stock', $this->sentRequests()[0]['data']['availability']);
    }

    public function test_inactive_and_deleted_products_are_removed(): void
    {
        $inactive = $this->product(['is_active' => false]);
        $deleted  = $this->product();
        $deleted->delete();

        $this->fakeMeta();
        $this->artisan('meta:sync-catalog')->assertSuccessful();

        $requests = collect($this->sentRequests());
        $this->assertSame(['DELETE'], $requests->pluck('method')->unique()->all());
        $this->assertEqualsCanonicalizing(
            [(string) $inactive->id, (string) $deleted->id],
            $requests->pluck('data.id')->all(),
        );
    }

    public function test_nothing_is_sent_when_not_configured(): void
    {
        config(['services.meta.catalog_id' => null]);
        $this->product();

        $this->fakeMeta();
        $this->artisan('meta:sync-catalog')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_failed_request_makes_the_command_fail(): void
    {
        $this->fakeMeta(403);
        $this->product();

        $this->expectException(\RuntimeException::class);

        $this->artisan('meta:sync-catalog')->run();
    }

    public function test_product_detail_exposes_the_ids_and_price_pixel_events_need(): void
    {
        $product = $this->product(['base_price' => 45000]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('product.base_price', '45000.00')
            ->assertJsonPath('product.variants.0.product_id', $product->id);
    }
}
