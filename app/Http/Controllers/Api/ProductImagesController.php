<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductImagesController extends Controller
{
    /**
     * Download a single product image as an attachment.
     * Only paths that belong to one of the product's variants are allowed,
     * so arbitrary files in storage can't be requested.
     */
    public function download(Request $request, string $slug)
    {
        $request->validate(['path' => ['required', 'string']]);

        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $path = $request->query('path');

        $belongsToProduct = ProductVariant::where('product_id', $product->id)
            ->whereNotNull('images')
            ->pluck('images')
            ->flatten()
            ->contains($path);

        $fullPath = public_path('storage/' . $path);

        if (! $belongsToProduct || ! file_exists($fullPath)) {
            return response()->json(['message' => 'Imagen no encontrada.'], 404);
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg';

        return response()->download($fullPath, $product->slug . '.' . $extension);
    }
}
