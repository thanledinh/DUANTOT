<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\OrderItem;

class OrderController extends Controller
{
    public function index(Request $request)
    {   
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)
                        ->with('product') 
                        ->get();
        
        return response()->json($orders, 200);
    }

    public function store(Request $request)
    {
        $request->validate([

            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id', 
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'id_promotion' => 'nullable|exists:promotions,id', 
            'sale' => 'required|numeric|min:0',
            'payment_method' => 'required',
            "note"=> "required",
            'variant_id',
        ]);
    
        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }
    
        $order = new Order();
        $order->user_id = $request->user_id; 
        $order->sale = $request->sale; 
        $order->product_id = $request->product_id; 
        $order->quantity = $request->quantity; 
        $order->total_price = $request->total_price; 
        $order->status = 'pending'; 
        $order->id_promotion = $request->id_promotion ?? null; 
        $order->save();
    
        return response()->json(['message' => 'Đơn hàng đã được tạo thành công.', 'order' => $order], 201);
    }

}
