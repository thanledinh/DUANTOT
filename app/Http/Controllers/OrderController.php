<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipping;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id', 
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);
        $order = Order::firstOrCreate(
            ['user_id' => $validated['user_id'], 'status' => 'pending'],
            ['order_date' => now(), 'total_price' => 0, 'payment_method' => '']
        );
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $validated['product_id'],
            'variant_id' => $validated['variant_id'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'sale' => false,
        ]);
        $order->total_price += $validated['price'] * $validated['quantity'];
        $order->save();
        return response()->json(['message' => '..', 'order' => $order, 'orderItem' => $orderItem]);
    }
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'shipping_address' => 'required|string|max:255',
            'shipping_method' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
        ]);
        $order = Order::where('user_id', $validated['user_id'])->where('status', 'pending')->firstOrFail();
        $order->update([
            'status' => 'paid',
            'payment_method' => $validated['payment_method'],
        ]);
        Shipping::create([
            'order_id' => $order->id,
            'shipping_address' => $validated['shipping_address'],
            'shipping_method' => $validated['shipping_method'],
            'shipping_cost' => 20.00, 
            'shipping_status' => 'pending',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'payment_method' => $validated['payment_method'],
        ]);
        return response()->json(['message' => '..']);
    }
}
