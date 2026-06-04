<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductColorsPdfController extends Controller
{
    public function download(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'variants' => fn ($q) => $q->with([
                    'variantAttributes.attribute',
                    'variantAttributes.attributeValue',
                    'media',
                ]),
            ])
            ->firstOrFail();

        // Group variants by color, picking the first image per color
        $variantGroups = [];
        foreach ($product->variants as $variant) {
            $colorName = null;
            foreach ($variant->variantAttributes as $va) {
                if ($va->attribute->code === 'COLOR') {
                    $colorName = $va->attributeValue->value;
                    break;
                }
            }

            $colorKey = $colorName ?? 'Sin color';

            if (! isset($variantGroups[$colorKey])) {
                $media = $variant->getFirstMedia('variant-images');
                $imagePath = $media ? $media->getPath() : null;
                $variantGroups[$colorKey] = [
                    'image' => $imagePath,
                    'skus'  => [],
                ];
            }

            $variantGroups[$colorKey]['skus'][] = $variant->sku;
        }

        if (empty($variantGroups)) {
            return response()->json(['message' => 'Este producto no tiene variantes.'], 404);
        }

        $pdf = Pdf::loadView('pdf.product-colors', [
            'product'       => $product,
            'variantGroups' => $variantGroups,
            'variantCount'  => $product->variants->count(),
        ])->setPaper('a4', 'portrait');

        $filename = 'colores-' . $product->slug . '.pdf';

        return $pdf->download($filename);
    }
}
