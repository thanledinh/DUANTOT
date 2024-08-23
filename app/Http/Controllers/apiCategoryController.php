<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class apiCategoryController extends Controller
{
    // Lấy danh sách tất cả các category
    public function index()
    {
        return Category::all();
    }

    // Tạo mới một category


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string', // Now the image is a Base64 string
        ]);
    
        $imagePath = null;
    
        if ($request->image) {
            // Decode Base64 string
            $imageData = $request->image;
            $imageName = uniqid() . '.jpg';
            $imagePath = 'images/category/' . $imageName;
    
            // Save the image to the public directory
            file_put_contents(public_path($imagePath), base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData)));
        }
    
        $category = new Category([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath, // Save the image path in the database
        ]);
    
        $category->save();
    
        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }
    
    

    // Lấy thông tin chi tiết một category
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return $category;
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
        ]);

        $category->name = $request->name;
        $category->description = $request->description;
        $category->image = $request->image;
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
}
