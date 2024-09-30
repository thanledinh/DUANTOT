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
        // Lấy danh sách sản phẩm yêu thích của người dùng
        $favorites = Wishlist::where('user_id', $user->id)->with('product')->get();
        return response()->json($favorites, 200);
    }

      
       public function store(Request $request)
       {
           // Xác thực người dùng bằng JWT
           $user = $request->user();
   
           // Kiểm tra dữ liệu đầu vào
           $request->validate([
               'variant_id' => 'required|exists:product_variants,id', // Kiểm tra variant_id
           ]);
   
           // Kiểm tra xem variant có liên kết với sản phẩm hợp lệ không
           $variant = ProductVariant::with('product')->find($request->variant_id);
           if (!$variant || !$variant->product) {
               return response()->json(['message' => 'Sản phẩm không hợp lệ.'], 400);
           }
   
           // Thêm sản phẩm vào danh sách yêu thích
           $wishlistItem = new Wishlist();
           $wishlistItem->user_id = $user->id; // Lấy user_id từ JWT
           $wishlistItem->variant_id = $request->variant_id; // Lưu variant_id
           $wishlistItem->save();
   
           return response()->json(['message' => 'Sản phẩm đã được thêm vào danh sách yêu thích.'], 201);
       }
   

    public function destroy(Request $request, $wishlist_id)
    {
        // Xác thực người dùng bằng JWT
        $user = $request->user();

         // Tìm sản phẩm yêu thích dựa trên user_id và wishlist_id
        $wishlistItem = Wishlist::where('user_id', $user->id)
                                ->where('id', $wishlist_id)
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
