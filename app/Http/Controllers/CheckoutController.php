<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Get or create cart for current session.
     */
    protected function getOrCreateCart(): ?Cart
    {
        $sessionKey = session()->getId();
        
        return Cart::where('session_key', $sessionKey)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Display checkout page.
     */
    public function index(): Response
    {
        $cart = $this->getOrCreateCart();

        if (!$cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => 'Sepetiniz boş. Lütfen önce ürün ekleyin.',
            ]);
        }

        $items = $cart->items()
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'price' => (float) $item->product->final_price,
                    ],
                ];
            });

        $subtotal = $items->sum(fn($item) => $item['product']['price'] * $item['quantity']);
        $total = $subtotal;

        return Inertia::render('Checkout/Index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }

    /**
     * Process checkout and create order.
     */
    public function store(Request $request)
    {
        Log::info('Checkout store başladı', [
            'request_data' => $request->all(),
            'session_id' => session()->getId(),
        ]);

        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'city' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'address' => 'required|string',
                'note' => 'nullable|string',
            ]);

            Log::info('Validation başarılı');

            $cart = $this->getOrCreateCart();

            Log::info('Cart kontrolü', [
                'cart_exists' => $cart !== null,
                'cart_id' => $cart?->id,
                'cart_items_count' => $cart?->items()->count() ?? 0,
            ]);

            if (!$cart || $cart->items()->count() === 0) {
                Log::warning('Cart boş veya bulunamadı');
                return back()->withErrors([
                    'cart' => 'Sepetiniz boş. Lütfen önce ürün ekleyin.',
                ]);
            }

            DB::beginTransaction();
            Log::info('Transaction başlatıldı');

            // Find or create customer by phone
            $customer = Customer::where('phone', $request->phone)->first();

            Log::info('Customer kontrolü', [
                'customer_exists' => $customer !== null,
                'customer_id' => $customer?->id,
                'phone' => $request->phone,
            ]);

            if (!$customer) {
                Log::info('Yeni customer oluşturuluyor');
                $customer = Customer::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'city' => $request->city,
                    'district' => $request->district,
                    'address' => $request->address,
                    'note' => $request->note,
                ]);
                Log::info('Customer oluşturuldu', ['customer_id' => $customer->id]);
            } else {
                Log::info('Mevcut customer güncelleniyor', ['customer_id' => $customer->id]);
                // Update customer info if provided
                $customer->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'city' => $request->city,
                    'district' => $request->district,
                    'address' => $request->address,
                    'note' => $request->note ?? $customer->note,
                ]);
                Log::info('Customer güncellendi');
            }

            // Calculate totals
            $items = $cart->items()->with('product')->get();
            $subtotal = $items->sum(fn($item) => $item->product->final_price * $item->quantity);
            $total = $subtotal;

            Log::info('Totals hesaplandı', [
                'items_count' => $items->count(),
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            // Create order
            Log::info('Order oluşturuluyor', [
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'total' => $total,
                'total_amount' => $total, // Deprecated field, but still required in DB
                'currency' => 'TRY',
            ]);

            Log::info('Order oluşturuldu', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
            ]);

            // Create order items
            Log::info('Order items oluşturuluyor', ['items_count' => $items->count()]);
            foreach ($items as $cartItem) {
                $lineTotal = $cartItem->product->final_price * $cartItem->quantity;
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->final_price, // Deprecated
                    'total_price' => $lineTotal, // Deprecated
                    'unit_price_snapshot' => $cartItem->product->final_price,
                    'line_total' => $lineTotal,
                    'product_name_snapshot' => $cartItem->product->name,
                    'sku_snapshot' => $cartItem->product->sku,
                ]);
                Log::info('Order item oluşturuldu', [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'line_total' => $lineTotal,
                ]);
            }

            // Clear cart
            Log::info('Cart temizleniyor', ['cart_id' => $cart->id]);
            $cart->items()->delete();
            $cart->delete();
            Log::info('Cart temizlendi');

            DB::commit();
            Log::info('Transaction commit edildi', ['order_id' => $order->id]);

            return redirect()->route('checkout.success', $order->id)
                ->with('success', 'Siparişiniz başarıyla oluşturuldu!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation hatası', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Sipariş oluşturma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'session_id' => session()->getId(),
            ]);
            
            return back()->withErrors([
                'error' => 'Sipariş oluşturulurken bir hata oluştu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Display order success page.
     */
    public function success(Order $order): Response
    {
        $order->load(['customer', 'orderItems.product']);

        return Inertia::render('Checkout/Success', [
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->status->value,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'created_at' => $order->created_at->format('d.m.Y H:i'),
                'customer' => [
                    'full_name' => $order->customer->full_name,
                    'phone' => $order->customer->phone,
                    'address' => $order->customer->address,
                    'city' => $order->customer->city,
                    'district' => $order->customer->district,
                ],
                'items' => $order->orderItems->map(fn($item) => [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ]),
            ],
        ]);
    }
}

