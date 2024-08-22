<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant; // Đảm bảo rằng bạn đã import model ProductVariant

class apiProductController extends Controller
{
    public function index()
    {
        $products = Product::with('variants')->get(); // Bao gồm biến thể khi lấy sản phẩm
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $product = Product::with('variants')->findOrFail($id); // Bao gồm biến thể khi lấy sản phẩm cụ thể
        return response()->json($product, 200);
    }

    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',  // Mô tả sản phẩm dưới dạng HTML
            'type' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'barcode' => 'nullable|string|max:255',
            'variants' => 'nullable|array',
            'variants.*.price' => 'required_with:variants|numeric',
            'variants.*.stock_quantity' => 'required_with:variants|integer',
            'variants.*.weight' => 'nullable|numeric',
            'variants.*.size' => 'nullable|string|max:255',
            'variants.*.flavor' => 'nullable|string|max:255',
            'variants.*.type' => 'nullable|string|max:255',
            'variants.*.image' => 'nullable|string', // Base64 string
        ]);

        // Tạo mới sản phẩm
        $product = Product::create($validatedData);

        // Xử lý và lưu trữ các biến thể
        if (!empty($validatedData['variants'])) {
            foreach ($validatedData['variants'] as $variantData) {
                // Kiểm tra và xử lý ảnh base64 của biến thể
                if (isset($variantData['image'])) {
                    $imageData = $variantData['image'];
                    // Tách phần header của base64 và dữ liệu thực tế
                    list($type, $imageData) = explode(';', $imageData);
                    list(, $imageData) = explode(',', $imageData);
                    $imageData = base64_decode($imageData);
                    $imageName = time() . '.jpg';
                    file_put_contents(public_path('images/products/') . $imageName, $imageData);

                    // Lưu đường dẫn vào cơ sở dữ liệu
                    $variantData['image'] = 'images/products/' . $imageName;
                }

                // Lưu biến thể vào cơ sở dữ liệu
                $product->variants()->create($variantData);
            }
        }

        return response()->json($product->load('variants'), 201);
    }

    public function update(Request $request, $id)
    {
        // Tìm sản phẩm
        $product = Product::findOrFail($id);

        // Cập nhật sản phẩm
        $product->update($request->all());

        // Nếu có biến thể mới được gửi trong request, có thể xử lý cập nhật hoặc tạo mới ở đây

        return response()->json($product->load('variants'), 200);
    }

    public function delete($id)
    {
        // Tìm và xóa sản phẩm
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }
}
