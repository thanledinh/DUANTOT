<?php

namespace App\Http\Controllers;

use App\Models\ProductType; // Import model
use App\Models\Product; // Import model
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index()
    {
        $productTypes = ProductType::orderBy('created_at', 'desc')->get();
    
        return response()->json($productTypes, 200);
    }
    

    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string|max:255',
        ]);

        $productType = ProductType::create($request->all()); // Tạo mới loại sản phẩm
        return response()->json($productType, 201);
    }

    public function show($id)
    {
        return ProductType::findOrFail($id); // Lấy loại sản phẩm theo ID
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type_name' => 'required|string|max:255',
        ]);

        $productType = ProductType::findOrFail($id);
        $productType->update($request->all()); // Cập nhật loại sản phẩm
        return response()->json($productType, 200);
    }

    public function destroy($id)
    {
        ProductType::destroy($id); // Xóa loại sản phẩm
        return response()->json(null, 204);
    }

    // sản phẩm theo id loại sản phẩm
    public function products($id)
    {
        $products = Product::where('product_type_id', $id)->get()->map(function ($product) {
            $product->variants->makeHidden(['cost_price']); // Ẩn trường cost_price của biến thể
            return $product;
        });
        return response()->json($products);
    }
}