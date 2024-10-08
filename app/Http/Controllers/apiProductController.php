<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;



class apiProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('variants')->get();
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $product = Product::with('variants')->findOrFail($id);
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
        // Tìm sản phẩm
        $product = Product::findOrFail($id);
    
        // Validate dữ liệu
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'type' => 'sometimes|required|string|max:255',
            'brand_id' => 'required|integer|exists:brands,id',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'image' => 'nullable|string',
            'barcode' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
        ]);
    
        // Xử lý hình ảnh sản phẩm nếu có
        if (isset($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image'], 'product');
        }
    
        // Cập nhật sản phẩm
        $product->update($validatedData);
    
        // Xử lý biến thể nếu có
        if (!empty($validatedData['variants'])) {
            foreach ($validatedData['variants'] as $variantData) {
                $variant = $product->variants()->find($variantData['id'] ?? null);
    
                // Nếu biến thể đã tồn tại, kiểm tra và xử lý hình ảnh
                if ($variant) {
                    // Nếu không có ảnh mới, giữ lại ảnh cũ
                    if (!isset($variantData['image'])) {
                        $variantData['image'] = $variant->image;
                    } else {
                        // Nếu có ảnh mới, xử lý và lưu ảnh mới
                        $variantData['image'] = $this->handleImageUpload($variantData['image'], 'variant');
                    }
                    $variant->update($variantData);
                } else {
                    // Nếu không tìm thấy biến thể, tạo mới biến thể
                    if (isset($variantData['image'])) {
                        $variantData['image'] = $this->handleImageUpload($variantData['image'], 'variant');
                    }
                    $product->variants()->create($variantData);
                }
            }
        }
    
        return response()->json($product->load('variants'), 200);
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
        $pageSize = $request->input('pageSize', 10); // Mặc định là 10 nếu không có tham số
        $pageNumber = $request->input('pageNumber', 1); // Mặc định là 1 nếu không có tham số
    
        // Phân trang sản phẩm
        $products = Product::with('variants')->paginate($pageSize, ['*'], 'page', $pageNumber);
    
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

        return response()->json($sortedProducts->values()->all());
    }


    public function relatedProducts($id)
    {
        // Lấy sản phẩm hiện tại
        $product = Product::findOrFail($id);

        // Lấy các sản phẩm liên quan dựa trên danh mục
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id) // Loại bỏ sản phẩm hiện tại
            ->take(6) // Giới hạn số lượng sản phẩm liên quan
            ->get();

        return response()->json($relatedProducts);
    }


    public function getLatestProducts(Request $request)
    {
        // Lấy 10 sản phẩm mới nhất, có thể thay đổi số lượng theo nhu cầu
        $latestProducts = Product::orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->take(10) 
            ->get();

        return response()->json($latestProducts);
    }


    public function getHotProducts(Request $request)
    {
       
        $hotProducts = Product::withCount('wishlists') // Đếm số lượng wishlist cho mỗi sản phẩm
            ->orderBy('wishlists_count', 'desc') // Sắp xếp theo số lượng wishlist
            ->take(10) 
            ->get();

        return response()->json($hotProducts);
    }

    public function getBestSellingProducts(Request $request)
    {
       
        $bestSellingProducts = Product::withCount('orderItems') // Đếm số lượng đơn hàng cho mỗi sản phẩm
            ->orderBy('order_items_count', 'desc') // Sắp xếp theo số lượng đơn hàng
            ->take(10) 
            ->get();

        return response()->json($bestSellingProducts);
    }

    // hiển thị sản phẩm theo danh mục
    public function getProductsByCategory($categoryId)
    {
        // Lấy tất cả subcategories của category với id = $categoryId
        $subcategories = Category::where('parent_id', $categoryId)->pluck('id');

        // Lấy tất cả sản phẩm thuộc về các subcategories
        $products = Product::with('variants')
            ->whereIn('category_id', $subcategories)
            ->get();

        return response()->json($products, 200);
    }

        // hiển thị sản phẩm theo danh mục
        public function getProductsByCategoryUrl($categoryUrl, Request $request) // Added Request $request parameter
        {
            // Tìm category dựa trên URL
            $category = Category::where('url', $categoryUrl)->firstOrFail();
        
            // Lấy tất cả subcategories của category với id = $category->id
            $subcategories = Category::where('parent_id', $category->id)->pluck('id');
        
            // Lấy tất cả sản phẩm thuộc về các subcategories với phân trang
            $pageSize = $request->input('pageSize', 10); // Mặc định là 10 nếu không có tham số
            $pageNumber = $request->input('pageNumber', 1); // Mặc định là 1 nếu không có tham số
    
            $products = Product::with('variants')
                ->whereIn('category_id', $subcategories)
                ->paginate($pageSize, ['*'], 'page', $pageNumber); // Added pagination
    
            return response()->json($products, 200);
        }


}