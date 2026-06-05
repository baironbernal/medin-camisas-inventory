<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductColorsPdfController extends Controller
{
    public function download(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $imageUrls = ProductVariant::where('product_id', $product->id)
            ->whereNotNull('images')
            ->pluck('images')
            ->flatten()
            ->filter()
            ->map(fn ($path) => url('storage/' . $path))
            ->unique()
            ->values()
            ->toArray();

        if (empty($imageUrls)) {
            return response()->json(['message' => 'Este producto no tiene imágenes.'], 404);
        }

        $pdf = Pdf::loadView('pdf.product-colors', [
            'product'   => $product,
            'imageUrls' => $imageUrls,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('colores-' . $product->slug . '.pdf');
    }
}
