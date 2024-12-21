<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class apiWishlistController extends Controller
{
 
   
    public function index(Request $request)
    {
        // Xác thực người dùng bằng JWT
        $user = $request->user();
        // Lấy danh sách sản phẩm yêu thích của người dùng, ẩn description
        $favorites = Wishlist::where('user_id', $user->id)
            ->with('product') // Giữ nguyên việc lấy toàn bộ thông tin sản phẩm
            ->get()
            ->map(function($item) {
                $item->product->description = null; // Ẩn trường description
                return $item;
            });
        return response()->json($favorites, 200);
    }

      
    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'product_id' => 'required|exists:products,id', 
        ]);

        $wishlistItem = new Wishlist();
        $wishlistItem->user_id = $user->id; // Lấy user_id từ JWT
        $wishlistItem->product_id = $request->product_id; // Lưu product_id
        $wishlistItem->save();

        return response()->json(['message' => 'Sản phẩm đã được thêm vào danh sách yêu thích.'], 201);
    }
   

    public function destroy(Request $request, $product_id)
    {
        // Xác thực người dùng bằng JWT
        $user = $request->user();

         // Tìm sản phẩm yêu thích dựa trên user_id và wishlist_id
        $wishlistItem = Wishlist::where('user_id', $user->id)
                                ->where('product_id', $product_id)
                                ->first();

        // Kiểm tra xem sản phẩm có trong danh sách yêu thích không
        if (!$wishlistItem) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong danh sách yêu thích.'], 404);
        }

        // Xóa sản phẩm khỏi danh sách yêu thích
        $wishlistItem->delete();

        return response()->json(['message' => 'Sản phẩm đã được xóa khỏi danh sách yêu thích.'], 200);
    }
    
}
