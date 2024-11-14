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

        $pageSize = $request->input('pageSize', 10);
        $pageNumber = $request->input('pageNumber', 1);

        // Khởi tạo query để lấy sản phẩm và các biến thể
        $query = Product::with(['variants:id,product_id,price,image,type,size,flavor,stock_quantity,sale'])
            ->select(['id', 'name', 'image', 'barcode', 'type', 'category_id', 'sale', 'brand_id', 'hot']);

        // Phân trang sản phẩm
        $products = $query->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($products);
    }



    public function show($id)
    {
        $product = Product::with(['variants:id,product_id,price,image,type,size,flavor,stock_quantity,sale'])
            ->findOrFail($id);

        return response()->json($product, 200);
    }

    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'brand_id' => 'sometimes|required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
            'image' => 'nullable|string', // Đảm bảo trường này là nullable
            'barcode' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.price' => 'required_with:variants|numeric',
            'variants.*.cost_price' => 'required_with:variants|numeric',
            'variants.*.stock_quantity' => 'required_with:variants|integer',
            'variants.*.size' => 'nullable|string|max:255',
            'variants.*.flavor' => 'nullable|string|max:255',
            'variants.*.type' => 'nullable|string|max:255',
            'variants.*.image' => 'nullable|string',
            'variants.*.sale' => 'nullable|numeric',
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
                'type' => 'sometimes|string|max:255',
                'brand_id' => 'nullable|integer|exists:brands,id', // Allow null
                'category_id' => 'sometimes|integer|exists:categories,id',
                'image' => 'nullable|string',
                'barcode' => 'nullable|string|max:255',
                'hot' => 'nullable|boolean',
                'variants' => 'nullable|array',
                'variants.*.id' => 'nullable|integer|exists:product_variants,id',  // Ensure variant exists
                'variants.*.price' => 'required_with:variants|numeric',
                'variants.*.cost_price' => 'required_with:variants|numeric',
                'variants.*.stock_quantity' => 'required_with:variants|integer',
                'variants.*.size' => 'nullable|string|max:255',
                'variants.*.flavor' => 'nullable|string|max:255',
                'variants.*.type' => 'nullable|string|max:255',
                'variants.*.image' => 'nullable|string',
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




    public function search(Request $request, $query)
    {
        $pageSize = $request->input('pageSize', 10); // Mặc định 10 sản phẩm mỗi trang
        $pageNumber = $request->input('pageNumber', 1); // Mặc định trang đầu tiên

        // Khởi tạo query để tìm kiếm sản phẩm
        $productsQuery = Product::with(['variants:id,product_id,price,image,type,size,flavor,stock_quantity,sale'])
            ->select(['id', 'name', 'image', 'barcode', 'type', 'category_id', 'sale', 'brand_id', 'hot'])
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('barcode', $query);

        // Phân trang sản phẩm
        $products = $productsQuery->paginate($pageSize, ['*'], 'page', $pageNumber);

        return response()->json($products);
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
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 nếu không có tham số
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là 1 nếu không có tham số

        $query = Product::with(['variants:id,product_id,price,image,type,size,flavor,stock_quantity,sale'])
            ->select(['id', 'name', 'image', 'barcode', 'type', 'category_id', 'sale', 'brand_id', 'hot']);
        // Phân trang sản phẩm
        $products = $query->paginate($pageSize, ['*'], 'page', $pageNumber);

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
    
        // Lấy tham số phân trang từ request, mặc định là 10 sản phẩm mỗi trang
        $pageSize = $request->input('pageSize', 10);
        $pageNumber = $request->input('pageNumber', 1);
    
        // Lấy tất cả các sản phẩm với biến thể
        $products = Product::with('variants')->paginate($pageSize, ['*'], 'page', $pageNumber);
    
        // Lấy tất cả các biến thể từ các sản phẩm
        $variants = $products->flatMap(function ($product) {
            return $product->variants->map(function ($variant) use ($product) {
                // Thêm thông tin sản phẩm vào mỗi biến thể
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variant_id' => $variant->id,
                    'price' => (float) $variant->price,
                    'image' => $variant->image,
                    'size' => $variant->size,
                    'flavor' => $variant->flavor,
                ];
            });
        });
    
        // Sắp xếp các biến thể theo giá
        if ($priceOrder === 'asc') {
            $sortedVariants = $variants->sortBy('price');
        } else {
            $sortedVariants = $variants->sortByDesc('price');
        }
    
        // Phân trang trên biến thể
        $sortedVariants = $sortedVariants->slice(($pageNumber - 1) * $pageSize, $pageSize);
    
        // Trả về phản hồi với thông tin đã phân trang
        return response()->json([
            'data' => $sortedVariants->values()->all(),
            'current_page' => $pageNumber,
            'last_page' => ceil($variants->count() / $pageSize),
            'per_page' => $pageSize,
            'total' => $variants->count(),
        ]);
    }




    public function getLatestProducts(Request $request)
    {
        // Lấy 10 sản phẩm mới nhất, có thể thay đổi số lượng theo nhu cầu
        $latestProducts = Product::with('variants') // Eager load variants
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->take(10)
            ->get()
            ->makeHidden(['description']); // Hide the description field

        return response()->json($latestProducts);
    }


    public function getHotProducts(Request $request)
    {
        // Lấy tham số phân trang từ request, mặc định là 10 sản phẩm mỗi trang
        $pageSize = $request->input('pageSize', 10);
        $pageNumber = $request->input('pageNumber', 1);

        // Dùng cache để lưu danh sách sản phẩm "hot"
        $hotProducts = Cache::remember('hot_products', 60, function () use ($pageSize, $pageNumber) {
            // Lấy danh sách sản phẩm hot và thông tin biến thể
            $query = Product::with('variants:id,product_id,price,stock_quantity,image')
                ->select('id', 'name', 'hot', 'image')
                ->where('hot', 1)
                ->orderBy('created_at', 'desc');

            // Phân trang dữ liệu
            $products = $query->paginate($pageSize, ['*'], 'page', $pageNumber);

            return $products;
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
        // Lấy tham số phân trang từ request, mặc định là 10 sản phẩm mỗi trang
        $pageSize = $request->input('pageSize', 10);
        $pageNumber = $request->input('pageNumber', 1);

        // Lấy danh sách sản phẩm bán chạy nhất và phân trang
        $bestSellingProducts = Product::with('variants')
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);

        // Ẩn các trường không cần thiết
        $bestSellingProducts->each(function ($product) {
            $product->makeHidden('description');

            $product->variants->each(function ($variant) {
                $variant->makeHidden('cost_price');
            });
        });

        return response()->json($bestSellingProducts);
    }



    public function getProductsByCategoryUrl($categoryUrl, Request $request)
    {
        // Tìm category dựa trên URL
        $category = Category::where('url', $categoryUrl)->firstOrFail();

        // Lấy tất cả subcategories của category với id = $category->id
        $subcategories = Category::where('parent_id', $category->id)->pluck('id');

        // Lấy tất cả sản phẩm thuộc về category hoặc subcategories với phân trang
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 nếu không có tham số
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là 1 nếu không có tham số

        if ($subcategories->isNotEmpty()) {
            // Nếu có subcategories, lấy sản phẩm từ subcategories
            $products = Product::with('variants')
                ->whereIn('category_id', $subcategories)
                ->paginate($pageSize, ['*'], 'page', $pageNumber);
        } else {
            // Nếu không có subcategories, lấy sản phẩm từ category hiện tại
            $products = Product::with('variants')
                ->where('category_id', $category->id)
                ->paginate($pageSize, ['*'], 'page', $pageNumber);
        }

        // Ẩn trường description của product và cost_price của variant
        $products->getCollection()->each(function ($product) {
            // Ẩn description của product
            $product->makeHidden('description');

            // Ẩn cost_price của variants
            $product->variants->each(function ($variant) {
                $variant->makeHidden('cost_price');
            });
        });

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

        // Lấy sản phẩm theo danh sách brand_id với phân trang
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 nếu không có tham số
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là 1 nếu không có tham số

        // Lọc các trường cần thiết cho bảng products và variants
        $productFields = ['id', 'name', 'image', 'barcode', 'type', 'category_id', 'sale', 'brand_id', 'hot'];
        $variantFields = ['id', 'product_id', 'price', 'image', 'type', 'size', 'flavor', 'stock_quantity', 'sale'];

        // Lấy sản phẩm từ các thương hiệu với phân trang
        $products = Product::with([
            'variants' => function ($query) use ($variantFields) {
                $query->select($variantFields); // Lọc trường cần thiết từ bảng variants
            }
        ])
            ->select($productFields) // Lọc trường cần thiết từ bảng products
            ->whereIn('brand_id', $brandIds)
            ->paginate($pageSize, ['*'], 'page', $pageNumber);

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
            ->get();
        $relatedProducts->each(function ($product) {
            // Ẩn description của product
            $product->makeHidden('description');

            // Ẩn cost_price của variants
            $product->variants->each(function ($variant) {
                $variant->makeHidden('cost_price');
            });
        });

        return response()->json($relatedProducts);
    }

}




