<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\VariantResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variants = $this->variants;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description'=> $this->description,
            'variants' => VariantResource::collection($variants),
            'available_attributes' => $this->buildAvailableAttributes($variants),
            'combination_index' => $this->buildCombinationIndex($variants),
        ];
    }

    private function buildAvailableAttributes($variants)
    {
        // Key: attribute name → [ raw_value => ['sort_order' => int, 'value' => mixed] ]
        // Using raw_value as the deduplication key avoids duplicates across variants
        // while preserving sort_order so we can sort afterwards.
        $entries = [];

        foreach ($variants as $variant) {
            foreach ($variant->variantAttributes as $variantAttribute) {
                $attributeName = $variantAttribute->attribute->name;
                $rawValue      = $variantAttribute->attributeValue->value;
                $sortOrder     = $variantAttribute->attributeValue->sort_order ?? 0;

                // Only store the first occurrence — deduplication by raw value
                if (isset($entries[$attributeName][$rawValue])) {
                    continue;
                }

                if ($variantAttribute->attribute->code === 'COLOR') {
                    $value = [
                        'name'      => $rawValue,
                        'hex_color' => $variantAttribute->attributeValue->hex_color,
                    ];
                } else {
                    $value = $rawValue;
                }

                $entries[$attributeName][$rawValue] = [
                    'sort_order' => $sortOrder,
                    'value'      => $value,
                ];
            }
        }

        $attributes = [];

        foreach ($entries as $name => $valueMap) {
            // Sort by sort_order ascending so sizes come out 24, 26, 28, 30 ...
            uasort($valueMap, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
            $attributes[$name] = array_values(array_map(fn ($e) => $e['value'], $valueMap));
        }

        return $attributes;
    }

    private function buildCombinationIndex($variants)
    {
        $index = [];

        // Fixed attribute order guarantees a predictable key format regardless
        // of the sort_order assigned to each attribute value in the database.
        // Frontend parses the key assuming this exact order: Talla|Color|Material
        $attributeOrder = ['Talla', 'Color', 'Material'];

        foreach ($variants as $variant) {

            // Collect all attributes by name first
            $attrMap = [];
            foreach ($variant->variantAttributes as $attr) {
                $attrMap[$attr->attribute->name] = $attr->attributeValue->value;
            }

            // Build segments in fixed order, skip attributes the variant doesn't have
            $segments = [];
            foreach ($attributeOrder as $name) {
                if (array_key_exists($name, $attrMap)) {
                    $segments[] = $attrMap[$name];
                }
            }

            // Use '|' as separator — attribute values never contain a pipe,
            // so parsing is always unambiguous (a '-' in e.g. "Azul-Oscuro" breaks split('-'))
            $key = implode('|', $segments);

            $index[$key] = [
                'variant_id' => $variant->id,
                'stock'      => $variant->inventories->sum('quantity_available'),
            ];
        }

        return $index;
    }
}
