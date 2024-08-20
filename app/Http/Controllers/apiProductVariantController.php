<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductVariant;
class apiProductVariantController extends Controller
{
    //
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
        $product = ProductVariant::create($request->all());
        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = ProductVariant::findOrFail($id);
        $product->update($request->all());
        return response()->json($product, 200);
    }

    public function delete($id)
    {
        $product = ProductVariant::findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }
}
