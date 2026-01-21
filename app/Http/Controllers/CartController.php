<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        protected ShippingService $shippingService
    ) {
    }

    /**
     * Get or create cart for current session.
     */
    protected function getOrCreateCart(): Cart
    {
        $sessionKey = session()->getId();
        
        $cart = Cart::where('session_key', $sessionKey)
            ->where('expires_at', '>', now())
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'session_key' => $sessionKey,
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $cart;
    }

    /**
     * Display cart page.
     */
    public function index(): Response
    {
        $cart = $this->getOrCreateCart();
        
        // Check PIN verification status first
        $isPinVerified = session()->get('price_pin_verified', false);
        
        $items = $cart->items()
            ->with('product.images')
            ->get()
            ->map(function ($item) use ($isPinVerified) {
                // Calculate price based on PIN status
                if ($isPinVerified) {
                    // PIN girildiyse final_price (şifreli satış fiyatı) kullan
                    $priceResult = $item->product->calculatePrice();
                    $price = (float) $priceResult->final;
                } else {
                    // PIN girilmediyse retail_price (perakende satış fiyatı) kullan
                    $price = (float) ($item->product->retail_price ?? $item->product->final_price);
                }
                
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'price' => $price,
                        'retail_price' => (float) ($item->product->retail_price ?? $item->product->final_price),
                        'image' => $item->product->default_image_url,
                    ],
                ];
            });

        $subtotal = $items->sum(fn($item) => $item['product']['price'] * $item['quantity']);
        
        // Calculate shipping cost
        $shippingCalculation = $this->shippingService->calculateShippingCost($subtotal, $isPinVerified);
        $shippingCost = $shippingCalculation->shippingCost;
        
        $total = $subtotal + $shippingCost;

        return Inertia::render('Cart/Index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'shipping_is_free' => $shippingCalculation->isFree,
            'shipping_remaining_amount' => $shippingCalculation->remainingAmount,
            'total' => $total,
            'cartCount' => $cart->total_items,
        ]);
    }

    /**
     * Add product to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = $this->getOrCreateCart();
        $product = Product::findOrFail($request->product_id);

        // Check if product is active
        if (!$product->is_active) {
            return back()->withErrors(['product' => 'Bu ürün şu anda satışta değil.']);
        }

        DB::transaction(function () use ($cart, $product, $request) {
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                ]);
            }
        });

        return back()->with('success', 'Ürün sepete eklendi.');
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $cart = $this->getOrCreateCart();

        // Verify cart item belongs to current cart
        if ($cartItem->cart_id !== $cart->id) {
            return back()->withErrors(['cart' => 'Geçersiz sepet işlemi.']);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return back()->with('success', 'Sepet güncellendi.');
    }

    /**
     * Remove item from cart.
     */
    public function remove(CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart();

        // Verify cart item belongs to current cart
        if ($cartItem->cart_id !== $cart->id) {
            return back()->withErrors(['cart' => 'Geçersiz sepet işlemi.']);
        }

        $cartItem->delete();

        return back()->with('success', 'Ürün sepetten kaldırıldı.');
    }

    /**
     * Get cart count for header.
     */
    public function count()
    {
        $cart = Cart::where('session_key', session()->getId())
            ->where('expires_at', '>', now())
            ->first();

        return response()->json([
            'count' => $cart ? $cart->total_items : 0,
        ]);
    }
}

