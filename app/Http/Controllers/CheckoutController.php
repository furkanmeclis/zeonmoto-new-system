<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

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
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'city' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'address' => 'required|string',
                'note' => 'nullable|string',
                'payment_method' => 'required|in:transfer,paytr_link',
            ]);

            $cart = $this->getOrCreateCart();

            if (!$cart || $cart->items()->count() === 0) {
                return back()->withErrors([
                    'cart' => 'Sepetiniz boş. Lütfen önce ürün ekleyin.',
                ]);
            }

            DB::beginTransaction();

            // Find or create customer by phone
            $customer = Customer::where('phone', $request->phone)->first();

            if (!$customer) {
                $customer = Customer::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone' => $request->phone,
                    'city' => $request->city,
                    'district' => $request->district,
                    'address' => $request->address,
                    'note' => $request->note,
                ]);
            } else {
                // Update customer info if provided
                $customer->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'city' => $request->city,
                    'district' => $request->district,
                    'address' => $request->address,
                    'note' => $request->note ?? $customer->note,
                ]);
            }

            // Calculate totals
            $items = $cart->items()->with('product')->get();
            $subtotal = $items->sum(fn($item) => $item->product->final_price * $item->quantity);
            $total = $subtotal;

            // Create order first to get order_no
            $paymentMethod = $request->payment_method;

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'total' => $total,
                'total_amount' => $total, // Deprecated field, but still required in DB
                'currency' => 'TRY',
                'payment_method' => $paymentMethod,
            ]);

            // Handle payment method - create PayTR link if needed
            $paymentLinkId = null;
            $paymentLink = null;

            if ($paymentMethod === 'paytr_link') {
                $customerName = "{$request->first_name} {$request->last_name}";
                $linkResult = $this->paymentService->createPayTRLink(
                    orderNo: $order->order_no,
                    amount: $total,
                    customerName: $customerName,
                    customerEmail: $request->email ?? null,
                    customerPhone: $request->phone ?? null,
                    orderId: $order->id,
                    maxInstallment: 12
                );

                if (!$linkResult['success']) {
                    DB::rollBack();
                    return back()->withErrors([
                        'payment' => $linkResult['message'] ?? 'Ödeme linki oluşturulamadı. Lütfen tekrar deneyin.',
                    ]);
                }

                $paymentLinkId = $linkResult['link_id'];
                $paymentLink = $linkResult['link'];

                // Update order with payment link ID
                $order->update([
                    'payment_link_id' => $paymentLinkId,
                ]);

                // Send SMS automatically if phone number is provided
                if (!empty($request->phone)) {
                    $this->paymentService->sendPaymentLinkSms(
                        $paymentLinkId,
                        $request->phone
                    );
                }
            }

            // Create order items
            foreach ($items as $cartItem) {
                $lineTotal = $cartItem->product->final_price * $cartItem->quantity;
                OrderItem::create([
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
            }

            // Clear cart
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            // Store payment link in session for success page
            if ($paymentLink) {
                session()->put("order_{$order->id}_payment_link", $paymentLink);
            }

            return redirect()->route('checkout.success', $order->id)
                ->with('success', 'Siparişiniz başarıyla oluşturuldu!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            
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

        // Get payment information
        $paymentInfo = null;
        if ($order->payment_method === 'transfer') {
            $paymentInfo = [
                'type' => 'transfer',
                'bank_account' => $this->paymentService->getBankAccountInfo(),
            ];
        } elseif ($order->payment_method === 'paytr_link') {
            // Try to get payment link from session first, then from order
            $paymentLink = session()->get("order_{$order->id}_payment_link");
            if (!$paymentLink && $order->payment_link_id) {
                // If link is not in session, we can't retrieve it from PayTR
                // This is a limitation - we should store the full link in the database
                // For now, we'll just indicate that payment link was created
                $paymentLink = null;
            }

            $paymentInfo = [
                'type' => 'paytr_link',
                'payment_link' => $paymentLink,
                'payment_link_id' => $order->payment_link_id,
            ];

            // Clear session
            session()->forget("order_{$order->id}_payment_link");
        }

        return Inertia::render('Checkout/Success', [
            'order' => [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->status->value,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'payment_method' => $order->payment_method,
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
            'payment_info' => $paymentInfo,
        ]);
    }
}

