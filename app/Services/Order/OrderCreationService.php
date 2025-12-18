<?php

namespace App\Services\Order;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\OrderStatus;
use App\Services\Pricing\PriceEngine;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    public function __construct(
        protected PriceEngine $priceEngine
    ) {
    }

    /**
     * Create order from cart with customer data.
     *
     * @param  array  $customerData  ['first_name', 'last_name', 'phone', 'city', 'district', 'address', 'note']
     */
    public function createFromCart(Cart $cart, array $customerData): Order
    {
        return DB::transaction(function () use ($cart, $customerData) {
            // Resolve or create customer
            $customer = $this->resolveCustomer($customerData);

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => OrderStatus::New,
                'currency' => 'TRY',
            ]);

            $subtotal = 0;

            // Convert cart items to order items with snapshots
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                
                // Calculate price using PriceEngine
                $priceResult = $this->priceEngine->calculate($product);
                
                $unitPrice = $priceResult->final;
                $lineTotal = $unitPrice * $cartItem->quantity;
                $subtotal += $lineTotal;

                // Create order item with snapshots
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $cartItem->quantity,
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'unit_price_snapshot' => $unitPrice,
                    'line_total' => $lineTotal,
                    // Keep deprecated fields for backward compatibility
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);
            }

            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'total' => $subtotal, // No discount for now, can be extended
                'total_amount' => $subtotal, // Backward compatibility
            ]);

            // Clear cart (optional - you might want to keep it for a while)
            // $cart->items()->delete();
            // $cart->delete();

            return $order->fresh(['customer', 'orderItems']);
        });
    }

    /**
     * Resolve customer by phone number or create new one.
     */
    protected function resolveCustomer(array $customerData): Customer
    {
        $phone = $customerData['phone'] ?? null;

        if ($phone) {
            $customer = Customer::where('phone', $phone)->first();
            
            if ($customer) {
                // Update customer data if provided
                $customer->update([
                    'first_name' => $customerData['first_name'] ?? $customer->first_name,
                    'last_name' => $customerData['last_name'] ?? $customer->last_name,
                    'city' => $customerData['city'] ?? $customer->city,
                    'district' => $customerData['district'] ?? $customer->district,
                    'address' => $customerData['address'] ?? $customer->address,
                    'note' => $customerData['note'] ?? $customer->note,
                ]);
                
                return $customer;
            }
        }

        // Create new customer
        return Customer::create([
            'first_name' => $customerData['first_name'] ?? '',
            'last_name' => $customerData['last_name'] ?? '',
            'phone' => $phone ?? '',
            'city' => $customerData['city'] ?? '',
            'district' => $customerData['district'] ?? '',
            'address' => $customerData['address'] ?? '',
            'note' => $customerData['note'] ?? '',
        ]);
    }
}

