<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVariant;

class apiProductVariantController extends Controller
{
    public function index()
    {
        $products = ProductVariant::all();
        $products->makeHidden(['cost_price']);
        return response()->json($products, 200);
    }
    public function show($id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json(['message' => 'Variant not found'], 404);
        }
        $variant->makeHidden(['cost_price']);
        return response()->json($variant, 200);
    }
    public function getProductsByProductId($product_id)
    {
        $products = ProductVariant::where('product_id', $product_id)->get();
        $products->makeHidden(['cost_price']);
        return response()->json($products);
    }


    public function getVariantByProductIdAndVariantId($product_id, $id)
{
    $variant = ProductVariant::where('product_id', $product_id)->where('id', $id)->first();

    if (!$variant) {
        return response()->json(['message' => 'Variant not found'], 404);
    }

    $variant->makeHidden(['cost_price']);
    return response()->json($variant, 200);
}
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer',
            'price' => 'required|numeric',
            'cost_price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'type' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'flavor' => 'nullable|string|max:255',
            'image' => 'nullable|string',
        ]);
        if (!empty($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image']);
        }
        $product = ProductVariant::create($validatedData);
        return response()->json($product, 201);
    }
    public function update(Request $request, $id)
    {
       
        $productVariant = ProductVariant::find($id);
        if (!$productVariant) {
            return response()->json(['message' => 'Variant không tồn tại'], 404);
        }
    
     
        $validatedData = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'price' => 'nullable|numeric',
            'cost_price' => 'required|numeric',
            'image' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:255',
            'flavor' => 'nullable|string|max:255',
            'stock_quantity' => 'nullable|integer',
        ]);
    
     
        if (!empty($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image']);
        }
    
      
        $productVariant->update($validatedData);
    
     
        return response()->json($productVariant, 200);
    }
    
    public function delete($id)
    {
        $product = ProductVariant::find($id);

        if (!$product) {
            return response()->json(['message' => 'Variant không tồn tài'], 404);
        }
        $product->delete();
        return response()->json(['message' => 'Xoá Variant thành công'], 204);
    }
    private function handleImageUpload($imageData)
    {
        list($type, $imageData) = explode(';', $imageData);
        list(, $imageData) = explode(',', $imageData);
        $imageData = base64_decode($imageData);
        $imageName = time() . '.jpg';
        file_put_contents(public_path('images/variants/') . $imageName, $imageData);
        return 'images/variants/' . $imageName;
    }
}
