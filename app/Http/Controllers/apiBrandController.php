<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;

class apiBrandController extends Controller
{
    // Thêm brand mới
    public function store(Request $request)
    {
        // Xác thực dữ liệu
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Tạo brand mới
        $brand = Brand::create($validatedData);

        return response()->json(['message' => 'Brand created successfully', 'brand' => $brand], 201);
    }
    // xóa brand 
    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully'], 200);
    }
    // lấy danh sách brand
    public function index()
    {
        $brands = Brand::all();
        return response()->json(['brands' => $brands], 200);
    }
    // lấy brand theo id
    public function show($id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json(['brand' => $brand], 200);
    }
    // cập nhật brand
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->update($request->all());

        return response()->json(['message' => 'Brand updated successfully', 'brand' => $brand], 200);
    }
    
}
