<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductVariantService
{
    /**
     * Generate all size × color × material variant combinations for a product.
     *
     * Images are stored as shared JSON paths in the `images` column —
     * every generated variant points to the same uploaded files.
     * No file is copied or duplicated.
     *
     * @param  \App\Models\Product  $product
     * @param  array  $data  Validated form data from the action
     * @return array{created: int, skipped: int}
     */
    public function generate($product, array $data): array
    {
        return DB::transaction(function () use ($product, $data) {
            // ── 1. Resolve attributes ─────────────────────────────────────────

            $sizeAttr     = Attribute::size()->firstOrFail();
            $colorAttr    = Attribute::color()->firstOrFail();
            $materialAttr = Attribute::material()->firstOrFail();

            // ── 2. Build size collection ──────────────────────────────────────

            $sizes = ! empty($data['full_curve'])
                ? $sizeAttr->values()->where('is_active', true)->get()
                : $sizeAttr->values()->whereIn('id', $data['sizes'] ?? [])->get();

            // ── 3. Build color collection ─────────────────────────────────────

            $colors = collect();
            if (! empty($data['colors'])) {
                $colors = $colorAttr->values()->whereIn('id', [$data['colors']])->get();
            }
            if (
                ! empty($data['other_color']) &&
                ! empty($data['other_color_value']) &&
                ! empty($data['other_color_hex'])
            ) {
                $colors->push(AttributeValue::create([
                    'attribute_id' => $colorAttr->id,
                    'value'        => $data['other_color_value'],
                    'code'         => strtoupper(substr($data['other_color_value'], 0, 3)),
                    'hex_color'    => $data['other_color_hex'],
                    'is_active'    => true,
                ]));
            }

            // ── 4. Build material collection ──────────────────────────────────

            $materials = collect();
            if (! empty($data['materials'])) {
                $materials = $materialAttr->values()->whereIn('id', [$data['materials']])->get();
            }
            if (! empty($data['other_material']) && ! empty($data['other_material_value'])) {
                $materials->push(AttributeValue::create([
                    'attribute_id' => $materialAttr->id,
                    'value'        => $data['other_material_value'],
                    'code'         => strtoupper(substr($data['other_material_value'], 0, 3)),
                    'is_active'    => true,
                ]));
            }

            // ── 5. Prepare shared image paths ─────────────────────────────────
            //
            // Filament FileUpload stores files once to storage/app/public/variants/
            // and returns an array of relative paths, e.g. ["variants/abc.jpg"].
            //
            // We store this same array in the `images` JSON column of EVERY
            // generated variant. All variants share the exact same file paths —
            // no file is copied or duplicated.
            //
            $sharedImages = array_values(
                array_filter((array) ($data['gallery_images'] ?? []), fn ($v) => ! empty($v))
            );
            Log::info('[ProductVariantService] Shared gallery images:', $sharedImages);

            // ── 6. Generate combinations ──────────────────────────────────────

            $createdCount = 0;
            $skippedCount = 0;
            $inventories  = $data['inventories'] ?? [];

            foreach ($sizes as $size) {
                foreach ($colors as $color) {
                    foreach ($materials as $material) {
                        $sku = strtoupper(
                            "{$product->reference_code}-{$size->code}-{$color->code}-{$material->code}"
                        );

                        if (ProductVariant::where('sku', $sku)->exists()) {
                            $skippedCount++;
                            continue;
                        }

                        $price = $data['use_base_price']
                            ? $product->base_price
                            : ($data['custom_price'] ?? $product->base_price);

                        // Create variant — images column receives the SAME path array
                        $variant = ProductVariant::create([
                            'sku'        => $sku,
                            'product_id' => $product->id,
                            'price'      => $price,
                            'cost'       => $product->base_price,
                            'weight'     => 0.3,
                            'barcode'    => '750' . str_pad((string) rand(1, 999999999), 9, '0', STR_PAD_LEFT),
                            'is_active'  => true,
                            'images'     => ! empty($sharedImages) ? $sharedImages : null,
                        ]);

                        // Attach variant attributes
                        VariantAttribute::insert([
                            ['product_variant_id' => $variant->id, 'attribute_id' => $sizeAttr->id,     'attribute_value_id' => $size->id],
                            ['product_variant_id' => $variant->id, 'attribute_id' => $colorAttr->id,    'attribute_value_id' => $color->id],
                            ['product_variant_id' => $variant->id, 'attribute_id' => $materialAttr->id, 'attribute_value_id' => $material->id],
                        ]);

                        // Create per-store inventory records
                        foreach ($inventories as $inventory) {
                            if (! empty($inventory['store_id'])) {
                                Inventory::create([
                                    'product_variant_id'  => $variant->id,
                                    'store_id'            => $inventory['store_id'],
                                    'quantity_available'  => $inventory['quantity_available'] ?? 0,
                                    'quantity_reserved'   => $inventory['quantity_reserved'] ?? 0,
                                    'quantity_in_transit' => $inventory['quantity_in_transit'] ?? 0,
                                    'min_quantity'        => $inventory['min_quantity'] ?? null,
                                    'max_quantity'        => $inventory['max_quantity'] ?? null,
                                    'reorder_point'       => $inventory['reorder_point'] ?? 0,
                                    'location'            => $inventory['location'] ?? null,
                                ]);
                            }
                        }

                        $createdCount++;
                    }
                }
            }

            return [
                'created' => $createdCount,
                'skipped' => $skippedCount,
            ];
        });
    }
}
