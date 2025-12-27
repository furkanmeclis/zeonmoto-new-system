<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    /**
     * Display shop page with products.
     */
    public function index(Request $request): Response
    {
        $query = Product::with(['images', 'categories'])
            ->where('is_active', true);

        // Category filter
        if ($request->has('category') && $request->category) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'sort_order');
        $sortDir = $request->get('direction', 'asc');

        if ($sortBy === 'price') {
            $query->orderBy('final_price', $sortDir);
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } else {
            $query->orderBy('sort_order', $sortDir);
        }

        $products = $query->paginate(24)->withQueryString();
        
        // Transform products for frontend
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->final_price,
                'retail_price' => (float) ($product->retail_price ?? $product->final_price),
                'base_price' => (float) $product->base_price,
                'image' => $product->default_image_url,
                'categories' => $product->categories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->display_name,
                    'slug' => $cat->slug,
                ]),
            ];
        });

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->display_name,
                'slug' => $cat->slug,
            ]);

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category' => $request->get('category'),
                'search' => $request->get('search'),
                'sort' => $sortBy,
                'direction' => $sortDir,
            ],
        ]);
    }

    /**
     * Display product detail page.
     */
    public function show(Request $request, Product $product): Response
    {
        if (!$product->is_active) {
            abort(404);
        }

        $product->load(['images' => function ($query) {
            $query->orderBy('is_primary', 'desc')
                  ->orderBy('sort_order');
        }, 'categories']);

        // Get cart info for this product
        $cart = Cart::where('session_key', $request->session()->getId())
            ->where('expires_at', '>', now())
            ->first();

        $cartItem = null;
        if ($cart) {
            $cartItem = $cart->items()
                ->where('product_id', $product->id)
                ->first();
        }

        // Sort images: primary first, then by sort_order
        $sortedImages = $product->images->sortBy([
            ['is_primary', 'desc'],
            ['sort_order', 'asc'],
        ])->values();

        // Get primary image or first image
        $primaryImage = $product->default_image_url ?? asset('logo.png');
        $allImages = $sortedImages->map(fn($img) => $img->url)->toArray();
        if (empty($allImages)) {
            $allImages = [$primaryImage];
        }

        // Canonical URL
        $canonicalUrl = url("/products/{$product->getRouteKey()}");

        // Formatted description
        $formattedDescription = $product->formatted_description;

        // Google Shopping Structured Data
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $formattedDescription,
            'image' => $allImages,
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) ($product->retail_price ?? $product->final_price),
                'priceCurrency' => 'TRY',
                'availability' => 'https://schema.org/InStock',
            ],
        ];

        // Set meta data to session
        $request->session()->put('meta_title', $product->name);
        $request->session()->put('meta_description', $formattedDescription);
        $request->session()->put('meta_image', $primaryImage);
        $request->session()->put('meta_url', $canonicalUrl);
        $request->session()->put('meta_type', 'product');
        $request->session()->put('structured_data', $structuredData);

        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->final_price,
            'retail_price' => (float) ($product->retail_price ?? $product->final_price),
            'base_price' => (float) $product->base_price,
            'description' => $formattedDescription,
            'images' => $sortedImages->map(fn($img) => [
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

        return Inertia::render('Shop/Show', [
            'product' => $productData,
            'cartItem' => $cartItem ? [
                'id' => $cartItem->id,
                'quantity' => $cartItem->quantity,
            ] : null,
        ]);
    }
}

