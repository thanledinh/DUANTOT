<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class apiCategoryController extends Controller
{
    // Lấy danh sách tất cả các category cùng với subcategories
    public function index()
    {
        $categories = Category::whereNull('parent_id')->get();

        foreach ($categories as $category) {
            $category->subcategories = Category::where('parent_id', $category->id)->get();
        }

        return response()->json($categories);
    }

    // Tạo mới một category
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id', // Thêm validation cho parent_id
            'url' => 'nullable|string|max:255', // Thêm validation cho url
        ]);

        $imagePath = null;

        if ($request->image) {
            $imagePath = $this->handleImageUpload($request->image, 'category');
        }

        $category = new Category([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
            'parent_id' => $request->parent_id, // Lưu parent_id
            'url' => $request->url, // Lưu url
        ]);

        $category->save();

        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }

    // Lấy thông tin một category theo ID
    public function show($id)
    {
        $category = Category::find($id); // Lấy category theo ID
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }

    // Lấy thông tin chi tiết một category bao gồm subcategories
    public function showFull($id)
    {
        $category = Category::with('subcategories')->find($id); // Lấy category cùng với subcategories
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return response()->json($category);
    }

    // Cập nhật thông tin một category
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id', // Thêm validation cho parent_id
            'url' => 'nullable|string|max:255', // Thêm validation cho url
        ]);

        // Xử lý upload hình ảnh nếu có
        if ($request->image) {
            $imagePath = $this->handleImageUpload($request->image, 'category'); // Gọi hàm upload hình ảnh
            $category->image = $imagePath; // Lưu tên hình ảnh vào cơ sở dữ liệu
        }

        // Cập nhật các trường khác
        $category->name = $request->name;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id; // Cập nhật parent_id
        $category->url = $request->url; // Cập nhật url
        $category->save();

        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }

    // Xóa một category
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    // Hàm xử lý upload hình ảnh
    private function handleImageUpload($imageData, $type)
    {
        $imageName = uniqid() . '.jpg';
        $imagePath = "images/{$type}/" . $imageName;
        file_put_contents(public_path($imagePath), base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData)));
        return $imagePath;
    }
    // Lấy danh sách tất cả các subcategories
    public function getSubcategories()
    {
        $subcategories = Category::whereNotNull('parent_id')->get();
        return response()->json($subcategories);
    }
    // Lấy danh sách tất cả các categories không có parent_id
    public function getParentCategories()
    {
        $parentCategories = Category::whereNull('parent_id')->get();
        return response()->json($parentCategories);
    }
    
}