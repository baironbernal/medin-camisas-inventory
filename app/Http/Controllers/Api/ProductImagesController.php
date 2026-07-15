<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use ZipArchive;

class ProductImagesController extends Controller
{
    /**
     * Download every image of a product bundled in a single ZIP file.
     * Reuses the same image-gathering logic as the colors PDF.
     */
    public function downloadZip(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $imagePaths = ProductVariant::where('product_id', $product->id)
            ->whereNotNull('images')
            ->pluck('images')
            ->flatten()
            ->filter()
            ->unique()
            ->map(fn ($path) => public_path('storage/' . $path))
            ->filter(fn ($fullPath) => file_exists($fullPath))
            ->values()
            ->toArray();

        if (empty($imagePaths)) {
            return response()->json(['message' => 'Este producto no tiene imágenes.'], 404);
        }

        $zipName = 'imagenes-' . $product->slug . '.zip';
        $zipPath = storage_path('app/' . $zipName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'No se pudo generar el archivo.'], 500);
        }

        foreach ($imagePaths as $index => $fullPath) {
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION) ?: 'jpg';
            $entryName = $product->slug . '-' . ($index + 1) . '.' . $extension;
            $zip->addFile($fullPath, $entryName);
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }
}
