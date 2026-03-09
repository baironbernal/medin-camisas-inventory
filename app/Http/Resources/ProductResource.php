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

        foreach ($variants as $variant) {

            $attributes = [];

            foreach ($variant->variantAttributes as $attr) {
                $attributes[$attr->attribute->name] = $attr->attributeValue->value;
            }

            $key = implode('-', $attributes); 

            $index[$key] = [
                'variant_id' => $variant->id,
                'stock' => $variant->inventories->sum('quantity_available')
            ];
        }

        return $index;
    }   
}
