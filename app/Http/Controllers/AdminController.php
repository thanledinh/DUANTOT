<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function products()
    {
        try {
            // Lấy sản phẩm trực tiếp từ cơ sở dữ liệu
            $products = Product::with('variants')->get();
            return view('admin.products.index', compact('products'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function categories()
    {
        $categories = Category::all();
        return view('admin.categories.index', compact('categories'));
    }
    public function create()
    {
        return view('admin.products.create');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.products.edit', compact('product'));
    }
}
