<?php

namespace App\Http\Controllers\ai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product; // Đảm bảo bạn đã import model Product
use Illuminate\Support\Facades\Cache; // Để sử dụng caching

class BoxChatAIController extends Controller
{
    public function searchProduct(Request $request)
    {
        $keywordsString = $request->query('keyword'); // Nhận chuỗi từ khóa từ query string
        $keywords = explode(',', $keywordsString); // Chia chuỗi thành mảng từ khóa

        $cacheKey = 'products_search_' . md5($keywordsString); // Tạo khóa cache dựa trên chuỗi từ khóa

        // Kiểm tra cache trước
        $products = Cache::remember($cacheKey, 60, function() use ($keywords) {
            // Tìm kiếm sản phẩm dựa trên các từ khóa
            return Product::where(function($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'LIKE', '%' . trim($keyword) . '%'); // Trim để loại bỏ khoảng trắng
                }
            })->take(5)->get(['id', 'name', 'image']); // Chỉ lấy 5 sản phẩm và các trường id, name, image
        });

        // Kiểm tra nếu không tìm thấy sản phẩm
        if ($products->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm nào.'], 404);
        }

        return response()->json($products);
    }
}