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
        $attributes = [];

        foreach ($variants as $variant) {
            foreach ($variant->variantAttributes as $variantAttribute) {
                $attributeName = $variantAttribute->attribute->name;
                $value = $variantAttribute->attributeValue->value;

                if($variantAttribute->attribute->code === 'COLOR') {
                    $value = [
                        'name' => $variantAttribute->attributeValue->value,
                        'hex_color' => $variantAttribute->attributeValue->hex_color
                    ];
                }
                $attributes[$attributeName][] = $value ;
            }
        }

        foreach ($attributes as $key => $values) {
            $attributes[$key] = array_values(array_unique($values, SORT_REGULAR));
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
