<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderPrintController extends Controller
{
    public function print(Order $order): View
    {
        $order->load(['customer', 'orderItems.product.images']);
        
        return view('orders.print', [
            'order' => $order,
        ]);
    }
}
