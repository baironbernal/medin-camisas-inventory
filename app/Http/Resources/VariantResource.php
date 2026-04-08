<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sizeAttr = $this->variantAttributes
            ->first(fn ($va) => strtoupper($va->attribute->code) === 'SIZE');

        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'price'       => $this->price,
            'images'      => $this->images,
            'inventories' => $this->inventories,
            'size'        => $sizeAttr?->attributeValue->code ?? '',
        ];
    }
}
