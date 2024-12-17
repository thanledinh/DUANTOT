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

    public function searchProductByAll(Request $request)
    {
        try {
            $keyword = $request->query('keyword');
            
            if (empty($keyword)) {
                return response()->json(['message' => 'Vui lòng nhập từ khóa tìm kiếm'], 400);
            }

            $cacheKey = 'products_full_search_' . md5($keyword);

            $products = Cache::remember($cacheKey, 60, function() use ($keyword) {
                $products = Product::with(['brand', 'category'])
                    ->where(function($query) use ($keyword) {
                        $query->where('name', 'LIKE', '%' . $keyword . '%')
                              ->orWhereRaw('LOWER(REGEXP_REPLACE(description, "<[^>]*>", "")) LIKE ?', ['%' . strtolower($keyword) . '%'])
                              ->orWhereHas('brand', function($q) use ($keyword) {
                                  $q->where('name', 'LIKE', '%' . $keyword . '%');
                              })
                              ->orWhereHas('category', function($q) use ($keyword) {
                                  $q->where('name', 'LIKE', '%' . $keyword . '%');
                              });
                    })
                    ->take(10)
                    ->get();

                // Lọc thêm kết quả dựa trên nội dung HTML đã được strip tags
                return $products->filter(function($product) use ($keyword) {
                    $cleanDescription = strip_tags($product->description);
                    return stripos($cleanDescription, $keyword) !== false;
                });
            });

            Log::info("Full search for keyword '$keyword' found " . $products->count() . " products");

            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'Không tìm thấy sản phẩm nào phù hợp với từ khóa.',
                    'keyword' => $keyword
                ], 404);
            }

            return response()->json([
                'message' => 'Tìm thấy ' . $products->count() . ' sản phẩm',
                'keyword' => $keyword,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            Log::error("Error in searchProductByAll: " . $e->getMessage());
            return response()->json([
                'message' => 'Đã xảy ra lỗi trong quá trình tìm kiếm: ' . $e->getMessage()
            ], 500);
        }
    }

    // tìm kiểm sản phẩm theo tất cả thông tin từ tên tới description 

}
