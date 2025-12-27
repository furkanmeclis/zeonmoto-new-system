<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentLink;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Handle PayTR payment callback.
     */
    public function handlePayTRCallback(Request $request)
    {
        // Validate callback
        if (!$this->paymentService->validatePayTRCallback($request->all())) {
            return response('Invalid hash', 400);
        }

        // Parse callback data
        $callback = $this->paymentService->parsePayTRCallback($request->all());

        if (!$callback) {
            return response('Invalid data', 400);
        }

        // Find payment link by merchant_oid (order_no)
        $paymentLink = PaymentLink::where('merchant_oid', $callback->merchant_oid)->first();

        if ($paymentLink) {
            // Update payment link with callback data
            $paymentLink->update([
                'status' => $callback->status === 'success' ? 'paid' : 'pending',
                'callback_data' => $request->all(),
                'callback_received_at' => now(),
            ]);
        }

        // Find order by merchant_oid (we'll use order_no as merchant_oid)
        $order = Order::where('order_no', $callback->merchant_oid)->first();

        if (!$order) {
            return response('Order not found', 404);
        }

        // Process based on payment status
        if ($callback->status === 'success') {
            // Payment successful
            $order->update([
                'status' => \App\OrderStatus::New, // Keep as New, can be changed later
            ]);

            // You can add additional logic here:
            // - Send confirmation email
            // - Update inventory
            // - Trigger webhooks
            // etc.

        } else {
            // Payment failed
            // Order status remains as New
            // You can add logic to handle failed payments if needed
        }

        // Always return OK to PayTR
        return response('OK', 200);
    }
}

