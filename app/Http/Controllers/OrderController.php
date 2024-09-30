<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();
        return response()->json($orders, 200);
    }
    public function show($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'oke'], 404);
        }
        return response()->json($order, 200);
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'id_promotion' => 'nullable|exists:promotions,id',
            'order_date' => 'required|date',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string',
            'payment_method' => 'required|string',
            'sale' => 'required|boolean',
        ]);

        $order = Order::create($validatedData);
        return response()->json($order, 201);
    }
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'oke'], 404);
        }
        $validatedData = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'id_promotion' => 'nullable|exists:promotions,id',
            'order_date' => 'sometimes|required|date',
            'total_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string',
            'payment_method' => 'sometimes|required|string',
            'sale' => 'sometimes|required|boolean',
        ]);
        $order->update($validatedData);
        return response()->json($order, 200);
    }
    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'oke'], 204);
    }
}
