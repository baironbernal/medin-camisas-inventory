<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    /**
     * Obtiene IDs de la categoría y todos sus descendientes (hijos, nietos...)
     */
    private function getAllCategoryIds($slug)
    {
        $category = Category::where('slug', $slug)->first();
        
        if (!$category) return collect();

        // Función recursiva interna para recolectar IDs
        $collectIds = function ($cat) use (&$collectIds) {
            $ids = collect([$cat->id]);
            foreach ($cat->children as $child) {
                $ids = $ids->merge($collectIds($child));
            }
            return $ids;
        };

        return $collectIds($category);
    }

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Resolución de Jerarquía de Categorías
        |--------------------------------------------------------------------------
        | Tomamos el slug más profundo enviado en la URL para iniciar la búsqueda.
        */
        $targetSlug = $request->subsubcategory ?: ($request->subcategory ?: $request->category);
        $categoryIds = $targetSlug ? $this->getAllCategoryIds($targetSlug) : collect();

        /*
        |--------------------------------------------------------------------------
        | 2. Query de Productos (Optimizado)
        |--------------------------------------------------------------------------
        */
        $products = Product::query()
            // Filtro por Categoría (Incluye descendientes)
            ->when($categoryIds->isNotEmpty(), function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            })

            ->when($request->filled('name'), fn ($q) =>
                $q->where('name', 'like', "%{$request->name}%")
            )

            ->when($request->filled('season_id'), fn ($q) =>
                $q->where('season_id', $request->season_id)
            )

            /* --- Filtros de Atributos --- */
            ->when($request->filled('color'), function ($q) use ($request) {
                $q->whereHas('variants.variantAttributes', function ($va) use ($request) {
                    $va->whereHas('attribute', fn($a) => $a->where('code', 'COLOR'))
                       ->whereHas('attributeValue', fn($v) => $v->where('value', 'like', "%{$request->color}%"));
                });
            })

            ->when($request->filled('size'), function ($q) use ($request) {
                $q->whereHas('variants.variantAttributes', function ($va) use ($request) {
                    $va->whereHas('attribute', fn($a) => $a->where('code', 'SIZE'))
                       ->whereHas('attributeValue', fn($v) => $v->where('value', 'like', "%{$request->size}%"));
                });
            })

            /* --- Filtros de Costo --- */
            ->when($request->filled('min_cost') || $request->filled('max_cost'), function ($q) use ($request) {
                $q->whereHas('variants', function ($v) use ($request) {
                    $v->when($request->filled('min_cost'), fn ($q2) => $q2->where('cost', '>=', $request->min_cost))
                      ->when($request->filled('max_cost'), fn ($q2) => $q2->where('cost', '<=', $request->max_cost));
                });
            })

            /* --- Ordenamiento --- */
            ->when($request->filled('order_by'), 
                fn ($q) => $q->orderBy($request->order_by, $request->get('order_dir', 'desc')),
                fn ($q) => $q->latest()
            )

            /* --- Conteo de Colores --- */
            ->withCount(['variants as colors_count' => function ($query) {
                $query->join('variant_attributes', 'product_variants.id', '=', 'variant_attributes.product_variant_id')
                      ->join('attributes', 'variant_attributes.attribute_id', '=', 'attributes.id')
                      ->where('attributes.code', 'COLOR')
                      ->select(DB::raw('count(distinct variant_attributes.attribute_value_id)'));
            }]) 

            ->with(['category'])
            ->paginate(10);

        return response()->json($products);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->with([
                'category',
                'variants' => function ($query) {
                    $query->whereHas('inventories', function ($q) {
                        $q->where('quantity_available', '>', 0);
                    })
                    ->with([
                        'inventories' => function ($q) {
                            $q->where('quantity_available', '>', 0);
                        },
                        'variantAttributes.attribute',
                        'variantAttributes.attributeValue',
                    ]);
                },
            ])
            ->firstOrFail();

        return new ProductResource($product);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
