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

    public function showWithoutHidden()
    {
        $products = ProductVariant::orderBy('created_at', 'desc')->get();
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
    public function getProductsByProductIdWithoutHidden($product_id)
    {
        $products = ProductVariant::where('product_id', $product_id)->get();
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

    public function getVariantByProductIdAndVariantIdWithoutHidden($product_id, $id)
    {
        $variant = ProductVariant::where('product_id', $product_id)->where('id', $id)->first();
        return response()->json($variant);
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

        // Kiểm tra sự tồn tại của sản phẩm với cùng type, size, hoặc flavor
        $existingProduct = ProductVariant::where('product_id', $validatedData['product_id'])
            ->where(function ($query) use ($validatedData) {
                if (!empty($validatedData['type'])) {
                    $query->where('type', $validatedData['type']);
                }
                if (!empty($validatedData['size'])) {
                    $query->where('size', $validatedData['size']);
                }
                if (!empty($validatedData['flavor'])) {
                    $query->where('flavor', $validatedData['flavor']);
                }
            })
            ->first();

        if ($existingProduct) {
            return response()->json(['message' => 'Sản phẩm với type, size, hoặc flavor đã tồn tại'], 400);
        }

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
        file_put_contents(public_path('images/variant/') . $imageName, $imageData);
        return 'images/variant/' . $imageName;
    }
}
