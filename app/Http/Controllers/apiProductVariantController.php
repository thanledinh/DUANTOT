<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVariant;

class apiProductVariantController extends Controller
{
    public function index()
    {
        $products = ProductVariant::all();
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return response()->json(['message' => 'Variant not found'], 404);
        }
        return response()->json($variant, 200);
    }

    public function getProductsByProductId($product_id)
    {
        $products = ProductVariant::where('product_id', $product_id)->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'size' => 'nullable|string|max:255',
            'flavor' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'sale' => 'nullable|numeric',
        ]);

        if (!empty($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image']);
        }

        $product = ProductVariant::create($validatedData);
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'size' => 'nullable|string|max:255',
            'flavor' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'sale' => 'nullable|numeric',
        ]);

        $product = ProductVariant::findOrFail($id);

        if (!empty($validatedData['image'])) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image']);
        }

        $product->update($validatedData);
        return response()->json($product, 200);
    }

    public function delete($id)
    {
        $product = ProductVariant::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }

    private function handleImageUpload($imageData)
    {
        list($type, $imageData) = explode(';', $imageData);
        list(, $imageData) = explode(',', $imageData);
        $imageData = base64_decode($imageData);
        $imageName = time() . '.jpg';
        file_put_contents(public_path('images/products/') . $imageName, $imageData);
        return 'images/products/' . $imageName;
    }
}