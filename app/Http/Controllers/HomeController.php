<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Display home page with featured products.
     */
    public function index(): Response
    {
        // Get featured products (active products, sorted by sort_order, limit 12)
        $products = Product::with(['images', 'categories'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(12)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->final_price,
                    'base_price' => (float) $product->base_price,
                    'image' => $product->default_image_url,
                    'categories' => $product->categories->map(fn($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->display_name,
                        'slug' => $cat->slug,
                    ]),
                ];
            });

        return Inertia::render('Home', [
            'products' => $products,
        ]);
    }
}

