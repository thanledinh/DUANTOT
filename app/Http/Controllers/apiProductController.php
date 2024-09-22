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