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
        $userId = Auth::id(); // Lấy ID người dùng hiện tại

        // Lấy tất cả đánh giá của người dùng hiện tại, không lọc theo `is_hidden`
        $reviews = ProductReview::where('user_id', $userId)
            ->get();

        return response()->json($reviews, 200);
    }




    public function showProduct_reviewforAdmin()
    {
        $reviews = ProductReview::orderBy('created_at', 'desc')
            ->with(
                'user:id,username',
                'product:id,name,image'
            )
            ->get();

        return response()->json($reviews, 200);
    }


    public function store(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Lấy user_id từ người dùng đã đăng nhập
        $userId = Auth::id();

        // Kiểm tra người dùng đã đăng nhập chưa
        if (!$userId) {
            return response()->json(['message' => 'Bạn phải đăng nhập để đánh giá sản phẩm.'], 401);
        }

        // Kiểm tra nếu người dùng đã đánh giá sản phẩm này rồi
        $reviewExists = ProductReview::where('user_id', $userId)
            ->where('product_id', $request->product_id)
            ->exists();

        if ($reviewExists) {
            return response()->json(['message' => 'Bạn đã đánh giá sản phẩm này trước đó.'], 409); // 409: Conflict
        }

        // Kiểm tra xem người dùng có đơn hàng nào với trạng thái thành công cho sản phẩm này không
        $orderExists = Order::where('user_id', $userId)
            ->where('status', 'Đã giao hàng') // Trạng thái thành công
            ->whereHas('items', function ($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->exists();

        if (!$orderExists) {
            return response()->json(['message' => 'Bạn chỉ có thể đánh giá sản phẩm mà bạn đã mua và đã giao thành công.'], 403);
        }

        // Tạo đánh giá mới
        $review = ProductReview::create([
            'product_id' => $request->product_id,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Đánh giá sản phẩm thành công.',
            'review' => $review,
        ], 201);
    }


    public function index($productId)
    {
        $reviews = ProductReview::where('product_id', $productId)
            ->where('is_hidden', 0) // Lọc chỉ những đánh giá không bị ẩn
            ->with('user:id,username') // Eager load để lấy thông tin user
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews, 200);
    }




    public function hide($id, Request $request)
    {
        $review = ProductReview::findOrFail($id);

        // Lấy trạng thái từ request (mặc định là 1)
        $isHidden = $request->input('is_hidden', 1);

        // Cập nhật trạng thái is_hidden
        $review->update(['is_hidden' => $isHidden]);

        $message = $isHidden ? 'Đánh giá đã được ẩn thành công.' : 'Đánh giá đã được hiển thị thành công.';

        return response()->json(['message' => $message, 'is_hidden' => $isHidden], 200);
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
