<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;

class apiProductController extends Controller
{
    public function index()
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
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
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
            'variants.*.image' => 'nullable|string',
        ]);

        $product = Product::create($validatedData);

        if (!empty($validatedData['variants'])) {
            foreach ($validatedData['variants'] as $variantData) {
                if (isset($variantData['image'])) {
                    $variantData['image'] = $this->handleImageUpload($variantData['image']);
                }
                $product->variants()->create($variantData);
            }
        }

        return response()->json($product->load('variants'), 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());
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