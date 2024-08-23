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
        return ProductVariant::find($id);
    }

    public function getProductsByProductId($product_id)
    {
        $products = ProductVariant::where('product_id', $product_id)->get();
        return response()->json($products);
    }

    public function getVariantByProductIdAndVariantId($product_id, $id)
    {
        $variant = ProductVariant::where('product_id', $product_id)
            ->where('id', $id)->get();
        return response()->json($variant);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer',
            'price' => 'required|numeric',
            'stock_quantity' => 'required|integer',
            'size' => 'nullable|string|max:255',
            'flavor' => 'nullable|string|max:255',
            'image' => 'nullable|string', // Expecting base64 string for image
        ]);

        if (!empty($validatedData['image'])) {
            $imageData = $validatedData['image'];
            list($type, $imageData) = explode(';', $imageData);
            list(, $imageData) = explode(',', $imageData);
            $imageData = base64_decode($imageData);
            $imageName = time() . '.jpg';
            file_put_contents(public_path('images/products/') . $imageName, $imageData);

            // Lưu đường dẫn vào cơ sở dữ liệu
            $validatedData['image'] = 'images/products/' . $imageName;
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
        ]);

        $product = ProductVariant::findOrFail($id);

        if (!empty($validatedData['image'])) {
            $imageData = $validatedData['image'];
            list($type, $imageData) = explode(';', $imageData);
            list(, $imageData) = explode(',', $imageData);
            $imageData = base64_decode($imageData);
            $imageName = time() . '.jpg';
            file_put_contents(public_path('images/products/') . $imageName, $imageData);

            // Lưu đường dẫn vào cơ sở dữ liệu
            $validatedData['image'] = 'images/products/' . $imageName;
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
}
