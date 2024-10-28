<?php

namespace App\Http\Controllers\ai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product; // Đảm bảo bạn đã import model Product
use Illuminate\Support\Facades\Cache; // Để sử dụng caching
use Illuminate\Support\Collects;
use Illuminate\Support\Facades\Log;

class BoxChatAIController extends Controller
{
    public function searchProduct(Request $request)
    {
        $keywordsString = $request->query('keyword'); // Nhận chuỗi từ khóa từ query string
        $keywords = explode(',', $keywordsString); // Chia chuỗi thành mảng từ khóa

        $products = collect(); // Tạo một collection để lưu trữ sản phẩm

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword); // Loại bỏ khoảng trắng
            $cacheKey = 'products_search_' . md5($keyword); // Tạo khóa cache cho từng từ khóa

            // Thêm thông tin gỡ lỗi
            Log::info("Searching for keyword: $keyword");

            // Kiểm tra cache trước
            $foundProducts = Cache::remember($cacheKey, 60, function() use ($keyword) {
                // Tìm kiếm sản phẩm dựa trên từ khóa
                return Product::where('name', 'LIKE', '%' . $keyword . '%')
                              ->take(5) // Lấy tối đa 2 sản phẩm cho mỗi từ khóa
                              ->get(['id', 'name', 'image']);
            });

            // Log kết quả tìm kiếm
            Log::info("Found products: " . $foundProducts->toJson());

            $products = $products->merge($foundProducts); // Gộp sản phẩm tìm được vào collection
        }

        // Kiểm tra nếu không tìm thấy sản phẩm
        if ($products->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm nào.'], 404);
        }

        return response()->json($products);
    }

}
