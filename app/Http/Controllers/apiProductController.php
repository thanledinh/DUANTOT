<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;

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
            'description' => 'required|string',
            'type' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'image' => 'nullable|string', // Đảm bảo trường này là nullable
            'barcode' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.price' => 'required_with:variants|numeric',
            'variants.*.stock_quantity' => 'required_with:variants|integer',
            'variants.*.weight' => 'nullable|numeric',
            'variants.*.size' => 'nullable|string|max:255',
            'variants.*.flavor' => 'nullable|string|max:255',
            'variants.*.type' => 'nullable|string|max:255',
            'variants.*.image' => 'nullable|string',
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
            'brand' => 'sometimes|required|string|max:255',
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
        $perPage = $request->input('per_page');
        $totalProducts = Product::count();
        if ($totalProducts > $perPage) {

            $products = Product::paginate($perPage);
        } else {
            $products = Product::all();
        }
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


}