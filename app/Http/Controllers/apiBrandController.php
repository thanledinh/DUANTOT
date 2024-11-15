<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;

class apiBrandController extends Controller
{
    private function handleImageUpload($imageData)
    {
        if (strpos($imageData, ';') === false || strpos($imageData, ',') === false) {
            throw new \Exception('Invalid image data format');
        }

        list($type, $imageData) = explode(';', $imageData);
        list(, $imageData) = explode(',', $imageData);
        $imageData = base64_decode($imageData);
        $imageName = time() . '.jpg';
        file_put_contents(public_path('images/brand/') . $imageName, $imageData);
        return 'images/brand/' . $imageName;
    }

    // Thêm brand mới
    public function store(Request $request)
    {
        // Xác thực dữ liệu
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|string',
        ]);

        // Xử lý hình ảnh sản phẩm nếu có
        if ($request->has('image')) {
            $validatedData['image'] = $this->handleImageUpload($validatedData['image'], 'brand');
        }

        // Tạo brand mới
        $brand = Brand::create($validatedData);

        return response()->json(['message' => 'Brand created successfully', 'brand' => $brand], 201);
    }

    // Cập nhật brand
    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        // Xử lý hình ảnh
        if ($request->hasFile('image')) {
            $brand->image = $this->handleImageUpload($request->file('image'), 'brand');
        } elseif ($request->input('image')) {
            $brand->image = $this->handleImageUpload($request->input('image'), 'brand');
        }

        // Cập nhật các trường khác
        $brand->update($request->except('image'));
        $brand->save();

        return response()->json(['message' => 'Brand updated successfully', 'brand' => $brand], 200);
    }

    // Xóa brand
    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();

        return response()->json(['message' => 'Brand deleted successfully'], 200);
    }

    // Lấy danh sách brand
    public function index()
    {
        $brands = Brand::all();
        return response()->json(['brands' => $brands], 200);
    }

    // Lấy brand theo id
    public function show($id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json(['brand' => $brand], 200);
    }
}

