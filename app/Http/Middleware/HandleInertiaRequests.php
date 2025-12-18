<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Filament panel route'larını Inertia'dan izole et
        if ($request->is('admin*')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return hash_file('xxh128', $manifest);
        }

        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Get cart count and items for guest users
        $cartCount = 0;
        $cartItems = [];
        
        if (!$request->is('admin*')) {
            $cart = Cart::where('session_key', $request->session()->getId())
                ->where('expires_at', '>', now())
                ->first();
            
            if ($cart) {
                $cartCount = $cart->total_items;
                
                // Get cart items as product_id => {id, quantity} map
                $cartItems = $cart->items()
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [
                            $item->product_id => [
                                'id' => $item->id,
                                'quantity' => $item->quantity,
                            ]
                        ];
                    })
                    ->toArray();
            }
        }

        return [
            ...parent::share($request),
            'cartCount' => $cartCount,
            'cartItems' => $cartItems, // product_id => quantity
        ];
    }
}
