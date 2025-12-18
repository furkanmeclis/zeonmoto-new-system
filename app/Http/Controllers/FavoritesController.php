<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoritesController extends Controller
{
    /**
     * Get product details for favorites page.
     * Accepts array of product IDs and returns their details.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:products,id',
        ]);

        $productIds = $request->input('ids', []);
        
        $products = Product::with(['images', 'categories'])
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->final_price,
                    'base_price' => (float) $product->base_price,
                    'image' => $product->default_image_url,
                    'images' => $product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => $img->url,
                        'is_primary' => $img->is_primary,
                    ]),
                    'categories' => $product->categories->map(fn($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->display_name,
                        'slug' => $cat->slug,
                    ]),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Display favorites page.
     */
    public function index(): Response
    {
        return Inertia::render('Favorites/Index');
    }
}

