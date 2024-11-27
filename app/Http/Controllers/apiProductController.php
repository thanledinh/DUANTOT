<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use App\Models\Brand;



class apiProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('variants')->get()->map(function ($product) {
            unset($product->description);
            $product->variants->makeHidden(['cost_price']);
            return $product;
        });
        
        return response()->json($products, 200);
    }
    public function showWithoutHidden(Request $request)
    {
        $products = Product::with('variants')->get()->map(function ($product) {
            return $product;
        });
        
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $product = Product::with('variants')->findOrFail($id);
        $product->variants->makeHidden(['cost_price']);
        return response()->json($product, 200);
    }

    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_type_id' => 'sometimes|required|integer|exists:brands,id',
            'brand_id' => 'sometimes|required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
            'image' => 'nullable|string', // Đảm bảo trường này là nullable
            'barcode' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.price' => 'required_with:variants|numeric',
            'variants.*.stock_quantity' => 'required_with:variants|integer',
            'variants.*.size' => 'nullable|string|max:255',
            'variants.*.flavor' => 'nullable|string|max:255',
            'variants.*.type' => 'nullable|string|max:255',
            'variants.*.image' => 'nullable|string',
            'variants.*.sale' => 'nullable|numeric',
            'variants.*.cost_price' => 'required_with:variants|numeric',
        ]);

        // Xử lý hình ảnh sản phẩm nếu có
        if (isset($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image'], 'product');
        }

        // Tạo sản phẩm mới
        $product = Product::create($validatedData);

        // Xử lý biến thể nếu có
        if (!empty($validatedData['variants'])) {
            foreach ($validatedData['variants'] as $variantData) {
                // Kiểm tra và xử lý ảnh biến thể nếu có
                if (isset($variantData['image'])) {
                    $variantData['image'] = $this->handleImageUpload($variantData['image'], 'variant');
                }
                // Tạo biến thể cho sản phẩm
                $product->variants()->create($variantData);
            }
        }

        return response()->json($product->load('variants'), 201);
    }



    public function update(Request $request, $id)
    {
    try {
        // Tìm sản phẩm dựa trên ID
        $product = Product::findOrFail($id);

        // Validate dữ liệu từ request
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'product_type_id' => 'nullable|integer|exists:brands,id',
            'brand_id' => 'nullable|integer|exists:brands,id', // Allow null
            'category_id' => 'sometimes|integer|exists:categories,id',
            'image' => 'nullable|string',
            'barcode' => 'nullable|string|max:255',
            'hot' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',  // Ensure variant exists
            'variants.*.price' => 'required_with:variants|numeric',
            'variants.*.stock_quantity' => 'required_with:variants|integer',
            'variants.*.size' => 'nullable|string|max:255',
            'variants.*.flavor' => 'nullable|string|max:255',
            'variants.*.type' => 'nullable|string|max:255',
            'variants.*.image' => 'nullable|string',
            'variants.*.cost_price' => 'required_with:variants|numeric',
        ]);

        // Xử lý hình ảnh sản phẩm nếu có
        if (isset($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image'], 'product');
        }

        // Cập nhật thông tin sản phẩm
        $product->update($validatedData);

        // Xử lý các biến thể nếu có
        if (!empty($validatedData['variants'])) {
            foreach ($validatedData['variants'] as $variantData) {
                // Tìm biến thể hiện tại (nếu có)
                $variant = $product->variants()->find($variantData['id'] ?? null);

                if ($variant) {
                    // Nếu biến thể tồn tại, cập nhật thông tin
                    if (!isset($variantData['image'])) {
                        $variantData['image'] = $variant->image;
                    } else {
                        // Xử lý ảnh mới cho biến thể
                        $variantData['image'] = $this->handleImageUpload($variantData['image'], 'variant');
                    }

                    // Cập nhật biến thể
                    $variant->update($variantData);
                } else {
                    // Nếu biến thể không tồn tại, tạo mới biến thể
                    if (isset($variantData['image'])) {
                        $variantData['image'] = $this->handleImageUpload($variantData['image'], 'variant');
                    }

                    // Tạo biến thể mới cho sản phẩm
                    $product->variants()->create($variantData);
                }
            }
        }

        // Trả về phản hồi với thông tin sản phẩm đã cập nhật, bao gồm cả các biến thể
        return response()->json($product->load('variants'), 200);
        
    } catch (\Exception $e) {
        // Bắt lỗi và trả về thông báo lỗi chi tiết
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    
    

    public function search($query)
    {
        $products = Product::with('variants')
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('barcode', $query)
            ->get();

        return response()->json($products, 200);
    }

    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }

    private function handleImageUpload($imageData, $folder)
    {
        // Tách phần type và dữ liệu base64
        list($type, $imageData) = explode(';', $imageData);
        list(, $imageData) = explode(',', $imageData);

        // Giải mã dữ liệu base64
        $imageData = base64_decode($imageData);

        // Tạo tên file hình ảnh với timestamp để đảm bảo tên file không trùng
        $imageName = time() . uniqid() . '.jpg'; // Bạn có thể thay đổi định dạng nếu cần
        $imageDirectory = public_path('images/' . $folder);
        $imagePath = $imageDirectory . '/' . $imageName;

        // Kiểm tra xem thư mục đã tồn tại chưa, nếu chưa thì tạo mới
        if (!file_exists($imageDirectory)) {
            mkdir($imageDirectory, 0755, true);
        }

        // Lưu hình ảnh vào thư mục
        file_put_contents($imagePath, $imageData);

        // Trả về đường dẫn hình ảnh để lưu vào cơ sở dữ liệu
        return 'images/' . $folder . '/' . $imageName;
    }



    public function products_paginate(Request $request)
    {
        // Lấy tất cả sản phẩm cùng với biến thể
        $products = Product::with('variants')->get()->map(function ($product) {
            $product->makeHidden(['description']); // Ẩn trường description
            $product->variants->makeHidden(['cost_price']); // Ẩn trường cost_price của biến thể
            return $product;
        });

        return response()->json($products);
    }


    public function sortByPrice(Request $request)
    {
        // Lấy tham số 'price' từ request, mặc định là 'asc'
        $priceOrder = $request->input('price', 'asc');
        $validOrders = ['asc', 'desc'];

        if (!in_array($priceOrder, $validOrders)) {
            return response()->json(['error' => 'Invalid price parameter'], 400);
        }

        // Lấy sản phẩm và sắp xếp theo giá của biến thể (chỉ lấy những sản phẩm có biến thể)
        $products = Product::with([
            'variants' => function ($query) use ($priceOrder) {
                $query->orderBy('price', $priceOrder);
            }
        ])->get()->filter(function ($product) {
            // Loại bỏ sản phẩm không có biến thể
            return $product->variants->isNotEmpty();
        });

        // Sắp xếp sản phẩm theo giá của biến thể
        if ($priceOrder === 'asc') {
            $sortedProducts = $products->sortBy(function ($product) {
                return $product->variants->first()->price;
            });
        } else {
            $sortedProducts = $products->sortByDesc(function ($product) {
                return $product->variants->first()->price;
            });
        }

        // Ẩn trường description và cost_price
        $sortedProducts->each(function ($product) {
            $product->makeHidden(['description']);
            $product->variants->makeHidden(['cost_price']);
        });

        return response()->json($sortedProducts->values()->all());
    }


    public function relatedProducts($id)
    {
        // Lấy sản phẩm hiện tại
        $product = Product::findOrFail($id);

        // Lấy các sản phẩm liên quan dựa trên danh mục
        $relatedProducts = Product::with('variants') // Tải trước biến thể
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id) // Loại bỏ sản phẩm hiện tại
            ->take(6) // Giới hạn số lượng sản phẩm liên quan
            ->get();

        // Ẩn trường description và cost_price
        $relatedProducts->each(function ($product) {
            $product->makeHidden(['description']);
            $product->variants->makeHidden(['cost_price']);
        });

        return response()->json($relatedProducts);
    }


    public function getLatestProducts(Request $request)
    {
        // Lấy 10 sản phẩm mới nhất, có thể thay đổi số lượng theo nhu cầu
        $latestProducts = Product::with('variants') // Eager load variants
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->take(20)
            ->get()
            ->makeHidden(['description']); // Hide the description field

        // Ẩn trường cost_price cho từng biến thể của mỗi sản phẩm
        $latestProducts->each(function ($product) {
            $product->variants->makeHidden(['cost_price']);
        });

        return response()->json($latestProducts);
    }


    public function getHotProducts(Request $request)
    {
        $hotProducts = Cache::remember('hot_products', 60, function () {
            return Product::with('variants:id,product_id,price,stock_quantity,image')
                ->select('id', 'name', 'hot', 'image')
                ->where('hot', 1)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    
        return response()->json($hotProducts);
    }
    public function removeMultipleHotStatus(Request $request)
    {
        // Lấy danh sách ID từ query string (hot-status=3,4,5)
        $productIds = explode(',', $request->query('hot-status'));
    
        // Tìm các sản phẩm có ID trong danh sách
        $products = Product::whereIn('id', $productIds)->get();
    
        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for the given IDs'], 404);
        }
    
        // Cập nhật trạng thái hot = 0 cho tất cả các sản phẩm tìm thấy
        foreach ($products as $product) {
            $product->hot = 0; // Cập nhật trạng thái hot thành 0 (gỡ bỏ)
            $product->save(); // Lưu thay đổi
        }
    
        return response()->json(['message' => 'Hot status removed successfully', 'products' => $products]);
    }
    
    

    public function updateMultipleHotStatus(Request $request)
    {
        // Lấy danh sách ID từ query string (ví dụ: hot-status=3,4,5)
        $productIds = explode(',', $request->query('hot-status'));

        // Tìm các sản phẩm có ID trong danh sách
        $products = Product::whereIn('id', $productIds)->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for the given IDs'], 404);
        }

        // Cập nhật trạng thái hot cho tất cả các sản phẩm tìm thấy
        foreach ($products as $product) {
            $product->hot = 1; // Cập nhật trạng thái hot thành 1
            $product->save(); // Lưu thay đổi
        }

        return response()->json(['message' => 'Products hot status updated successfully', 'products' => $products]);
    }


    public function getBestSellingProducts(Request $request)
    {

        $bestSellingProducts = Product::with('variants')->withCount('orderItems') // Đếm số lượng đơn hàng cho mỗi sản phẩm
            ->orderBy('order_items_count', 'desc') // Sắp xếp theo số lượng đơn hàng
            ->take(12)
            ->get();

        return response()->json($bestSellingProducts);
    }

    // hiển th sản phẩm theo danh mc
    public function getProductsByCategoryUrl($categoryUrl, Request $request)
    {
        // Tìm category dựa trên URL
        $category = Category::where('url', $categoryUrl)->firstOrFail();

        // Lấy tất cả subcategories của category với id = $category->id
        $subcategories = Category::where('parent_id', $category->id)->pluck('id');

        // Lấy tất cả sản phẩm thuộc về category hoặc subcategories
        if ($subcategories->isNotEmpty()) {
            // Nếu có subcategories, lấy sản phẩm từ subcategories
            $products = Product::with('variants')
                ->whereIn('category_id', $subcategories)
                ->get();
        } else {
            // Nếu không có subcategories, lấy sản phẩm từ category hiện tại
            $products = Product::with('variants')
                ->where('category_id', $category->id)
                ->get();
        }

        return response()->json($products, 200);
    }
    public function getProductPrice($id)
    {
        $product = Product::findOrFail($id);
        return response()->json([
            'message' => 'Product price retrieved successfully.',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ]
        ], 200);
    }

    // lấy sản phẩm theo brand name
    public function getProductsByBrand($brandNames, Request $request)
    {
        // Tách danh sách tên thương hiệu thành mảng
        $brandNamesArray = explode(',', $brandNames);

        // Tìm tất cả các thương hiệu dựa trên tên
        $brands = Brand::whereIn('name', $brandNamesArray)->get();

        // Lấy tất cả các ID của thương hiệu
        $brandIds = $brands->pluck('id');

        // Lấy sản phẩm theo danh sách brand_id
        $products = Product::with('variants')->whereIn('brand_id', $brandIds)->get();

        return response()->json($products);
    }

    // sản phẩm liên quan
    public function getRelatedProducts($id)
    {
        // Lấy sản phẩm hiện tại
        $product = Product::findOrFail($id);

        // Lấy các sản phẩm liên quan dựa trên danh mục và sắp xếp ngẫu nhiên, bao gồm cả biến thể
        $relatedProducts = Product::with('variants') // Tải trước biến thể
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id) // Loại bỏ sản phẩm hiện tại
            ->inRandomOrder() // Sắp xếp ngẫu nhiên
            ->take(6) // Giới hạn số lượng sản phẩm liên quan
            ->get()
            ->makeHidden(['description']); // Ẩn trường description

        return response()->json($relatedProducts);
    }

}




