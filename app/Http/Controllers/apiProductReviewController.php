<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class apiProductReviewController extends Controller
{


    public function showProduct_reviewforUsser()
    {
        $reviews = ProductReview::where('is_hidden', false)
            ->get();

        return response()->json($reviews, 200);
    }


    public function showProduct_reviewforAdmin()
    {
        $reviews = ProductReview::orderBy('created_at', 'desc')
            ->get();
    
        return response()->json($reviews, 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // Lấy user_id từ người dùng đã đăng nhập
        $userId = Auth::id();

        // Kiểm tra xem người dùng có đơn hàng nào với trạng thái thành công cho sản phẩm này không
        $orderExists = Order::where('user_id', $userId)
            ->where('status', 'Đã giao hàng') // Giả sử trạng thái thành công là 'success'
            ->whereHas('items', function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->exists();
        if (!$orderExists) {
            return response()->json(['message' => 'Bạn chỉ có thể đánh giá sản phẩm từ các đơn hàng thành công.'], 403);
        }

        $review = ProductReview::create([
            'product_id' => $request->product_id,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review, 201);
    }

    public function index($productId)
    {
        $reviews = ProductReview::where('product_id', $productId)->get();

        return response()->json($reviews, 200);
    }


    public function hide($id)
    {
        $review = ProductReview::findOrFail($id);

        // Kiểm tra xem người dùng có phải là admin không
        if (!Auth::user()->is_admin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->update(['is_hidden' => true]);

        return response()->json(['message' => 'Sản phẩm được ẩn thành công.'], 200);
    }


    public function destroy($id)
    {
        $review = ProductReview::findOrFail($id);

        // Kiểm tra xem người dùng có phải là admin không
        if (Auth::user()->is_admin()) {
            // Admin có thể xóa bất kỳ đánh giá nào
            $review->delete();
            return response()->json(['message' => 'Sản phẩm được xóa thành công.'], 200);
        }

        // Người dùng chỉ có thể xóa đánh giá của chính họ
        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Bạn không có quyền xóa đánh giá này'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Sản phẩm được xóa thành công.'], 200);
    }


   

   
}
