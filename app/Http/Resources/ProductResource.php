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
            'slug' => $this->slug,
            'description'=> $this->description,
            'wholesaler_price' => $this->wholesaler_price,
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
                    $segments[] = $this->normalizeSegment($attrMap[$name]);
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

    /**
     * Normalizes a segment value for combination index key consistency:
     * - Force UTF-8 encoding
     * - Transliterate/remove accents (tildes)
     * - Convert to UPPERCASE
     * - Strip all whitespace
     * - Strip any character that is not alphanumeric, hyphen, or underscore
     */
    private function normalizeSegment($value): string
    {
        $str = (string)$value;

        // Transliterator removes accents and converts to ASCII (Latin-ASCII) and uppercase
        if (class_exists('Transliterator')) {
            $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Upper');
            if ($transliterator) {
                $str = $transliterator->transliterate($str);
            }
        } else {
            // Manual fallback for removing accents and uppercase
            $unwanted_array = [
                'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
                'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
                'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a',
                'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i',
                'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
                'û'=>'u', 'ü'=>'u', 'y'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
            ];
            $str = strtr($str, $unwanted_array);
            $str = strtoupper($str);
        }

        // Remove all whitespace
        $str = preg_replace('/\s+/', '', $str);

        // Keep only alphanumeric characters, hyphens, and underscores
        $str = preg_replace('/[^A-Z0-9_-]/', '', $str);

        return $str;
    }
}
