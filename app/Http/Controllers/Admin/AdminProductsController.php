<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;

class AdminProductsController extends Controller
{

    public function getProducts(Request $request)
    {
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 sản phẩm mỗi trang
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là trang đầu tiên

        // Truy vấn sản phẩm với thông tin biến thể và phân trang
        $products = Product::with('variants')
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($products, 200);
    }


    // Lấy danh sách các biến thể của sản phẩm có stock quantity từ thấp tới cao
    public function index(Request $request)
    {
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 sản phẩm mỗi trang
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là trang đầu tiên

        // Truy vấn sản phẩm và tải trước thông tin biến thể
        $products = Product::with('variants')
            ->get()
            ->map(function ($product) {
                // Tìm biến thể có số lượng tồn kho thấp nhất
                $lowestStockVariant = $product->variants->sortBy('stock_quantity')->first();

                // Tạo mảng chứa tất cả các biến thể
                $variants = $product->variants->map(function ($variant) {
                    return [
                        'variant_id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock_quantity' => $variant->stock_quantity,
                    ];
                });

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'lowest_stock' => $lowestStockVariant ? $lowestStockVariant->stock_quantity : 0,
                    'variants' => $variants,
                ];
            })
            ->sortBy('lowest_stock')
            ->values();

        // Phân trang danh sách sản phẩm
        $paginatedProducts = $products->forPage($pageNumber, $pageSize);

        return response()->json([
            'data' => $paginatedProducts->values(),
            'current_page' => $pageNumber,
            'page_size' => $pageSize,
            'total' => $products->count(),
        ]);
    }


    public function getProductVariants($productId)
    {
        $product = Product::with('variants')->findOrFail($productId);

        $variants = $product->variants->map(function ($variant) {
            return [
                'variant_id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'stock_quantity' => $variant->stock_quantity,
                'price' => $variant->price,
                'cost_price' => $variant->cost_price,
            ];
        });

        return response()->json([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variants' => $variants
        ]);
    }

    public function getProductsWithLowestStock(Request $request)
    {
        $pageSize = $request->input('pageSize', 10); // Số sản phẩm trên mỗi trang (mặc định là 10)
        $pageNumber = $request->input('pageNumber', 1); // Trang hiện tại (mặc định là trang 1)

        $products = Product::with([
            'variants' => function ($query) {
                $query->orderBy('stock_quantity', 'asc');
            }
        ])
            ->get()
            ->map(function ($product) {
                $lowestStockVariant = $product->variants->first();
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'lowest_stock' => $lowestStockVariant ? $lowestStockVariant->stock_quantity : 0,
                    'variant_id' => $lowestStockVariant ? $lowestStockVariant->id : null,
                    'size' => $lowestStockVariant ? $lowestStockVariant->size : null,
                    'color' => $lowestStockVariant ? $lowestStockVariant->color : null,
                ];
            })
            ->sortBy('lowest_stock')
            ->values();

        // Phân trang danh sách sản phẩm
        $paginatedProducts = $products->forPage($pageNumber, $pageSize);

        return response()->json([
            'data' => $paginatedProducts->values(),
            'current_page' => $pageNumber,
            'page_size' => $pageSize,
            'total' => $products->count(),
        ]);
    }


    public function getProductsByStockQuantity(Request $request, $minStock, $maxStock)
    {
        $pageSize = $request->input('pageSize', 10); // Số sản phẩm trên mỗi trang (mặc định là 10)
        $pageNumber = $request->input('pageNumber', 1); // Trang hiện tại (mặc định là trang 1)

        $products = Product::with([
            'variants' => function ($query) use ($minStock, $maxStock) {
                $query->whereBetween('stock_quantity', [$minStock, $maxStock])
                    ->orderBy('stock_quantity', 'asc');
            }
        ])
            ->whereHas('variants', function ($query) use ($minStock, $maxStock) {
                $query->whereBetween('stock_quantity', [$minStock, $maxStock]);
            })
            ->get()
            ->map(function ($product) {
                $lowestStockVariant = $product->variants->first();
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'lowest_stock' => $lowestStockVariant ? $lowestStockVariant->stock_quantity : null,
                    'variant_id' => $lowestStockVariant ? $lowestStockVariant->id : null,
                    'size' => $lowestStockVariant ? $lowestStockVariant->size : null,
                    'color' => $lowestStockVariant ? $lowestStockVariant->color : null,
                ];
            })
            ->sortBy('lowest_stock')
            ->values();

        // Phân trang danh sách sản phẩm
        $paginatedProducts = $products->forPage($pageNumber, $pageSize);

        return response()->json([
            'data' => $paginatedProducts->values(),
            'current_page' => $pageNumber,
            'page_size' => $pageSize,
            'total' => $products->count(),
        ]);
    }


    public function updateStockQuantity($productId, $variantId, $newStockQuantity)
    {
        // Lấy ID người dùng
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $product = Product::findOrFail($productId);
        $variant = $product->variants()->findOrFail($variantId);

        $oldQuantity = $variant->stock_quantity;
        $variant->stock_quantity = $newStockQuantity;
        $variant->save();

        StockHistory::create([
            'variant_id' => $variantId,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newStockQuantity,
            'changed_by' => $userId, // Lưu ID người dùng thay vì tên
        ]);

        return response()->json(['message' => 'Stock quantity updated successfully', 'changed_by' => $userId]);
    }

    public function updateProductStatus($productId, $isActive)
    {
        $product = Product::findOrFail($productId);
        $product->is_active = $isActive;
        $product->save();

        return response()->json(['message' => 'Product status updated successfully']);
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with('variants'); // Tải trước thông tin biến thể

        // Áp dụng bộ lọc tìm kiếm
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // Thiết lập phân trang
        $pageSize = $request->input('pageSize', 10); // Số sản phẩm mỗi trang (mặc định là 10)
        $pageNumber = $request->input('pageNumber', 1); // Trang hiện tại (mặc định là 1)

        // Thực hiện phân trang trên truy vấn
        $products = $query->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($products);
    }


    // Lấy lịch sử thay đổi tồn kho
    public function getStockHistory(Request $request, $variantId)
    {
        // Thiết lập phân trang
        $pageSize = $request->input('pageSize', 10); // Số bản ghi mỗi trang (mặc định là 10)
        $pageNumber = $request->input('pageNumber', 1); // Trang hiện tại (mặc định là 1)

        // Truy vấn lịch sử tồn kho của biến thể và phân trang
        $history = StockHistory::where('variant_id', $variantId)
            ->orderBy('changed_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json( $history);
    }


    // API thông báo khi tồn kho dưới 10
    public function getLowStockAlerts(Request $request)
    {
        // Thiết lập phân trang
        $pageSize = $request->input('pageSize', 10); // Số bản ghi mỗi trang (mặc định là 10)
        $pageNumber = $request->input('pageNumber', 1); // Trang hiện tại (mặc định là 1)
    
        // Truy vấn sản phẩm và biến thể có số lượng tồn kho thấp và phân trang
        $products = Product::with([
            'variants' => function ($query) {
                $query->where('stock_quantity', '<', 10);
            }
        ])
            ->whereHas('variants', function ($query) {
                $query->where('stock_quantity', '<', 10);
            })
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
    
        // Chuẩn bị dữ liệu cho mỗi sản phẩm và biến thể của nó
        $data = $products->map(function ($product) {
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'variant_id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock_quantity' => $variant->stock_quantity,
                    ];
                }),
            ];
        });
    
        // Trả về dữ liệu dưới dạng JSON với thông tin phân trang
        return response()->json($data);
    }
    
}
