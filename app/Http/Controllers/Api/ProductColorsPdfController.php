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
            ->unique()
            ->map(function ($path) {
                $fullPath = public_path('storage/' . $path);
                if (! file_exists($fullPath)) {
                    return null;
                }
                $mime = mime_content_type($fullPath);
                $data = base64_encode(file_get_contents($fullPath));
                return "data:{$mime};base64,{$data}";
            })
            ->filter()
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
