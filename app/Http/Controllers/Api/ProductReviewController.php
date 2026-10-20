<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Request $request, string $slug): JsonResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();

        $reviews = $product->reviews()
            ->visible()
            ->latest()
            ->get(['id', 'name', 'comment', 'created_at']);

        $hasReviewed = $product->reviews()
            ->where('ip_hash', ProductReview::hashIp($request->ip()))
            ->exists();

        return response()->json([
            'success'      => true,
            'data'         => $reviews,
            'has_reviewed' => $hasReviewed,
        ]);
    }

    public function store(Request $request, string $slug): JsonResponse
    {
        $product = Product::active()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $ipHash = ProductReview::hashIp($request->ip());

        // One review per visitor (IP) per product
        if ($product->reviews()->where('ip_hash', $ipHash)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya dejaste una reseña para este producto. ¡Gracias!',
            ], 409);
        }

        $review = $product->reviews()->create([
            'name'    => strip_tags($validated['name']),
            'comment' => strip_tags($validated['comment']),
            'ip_hash' => $ipHash,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $review->only(['id', 'name', 'comment', 'created_at']),
        ], 201);
    }
}
