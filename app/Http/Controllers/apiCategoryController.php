<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class apiCategoryController extends Controller
{
    public function index()
    {
        return Category::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        $imagePath = null;

        if ($request->image) {
            $imagePath = $this->handleImageUpload($request->image, 'category');
        }

        $category = new Category([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $imagePath,
        ]);

        $category->save();

        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        return $category;
    }

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

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }

    private function handleImageUpload($imageData, $type)
    {
        $imageName = uniqid() . '.jpg';
        $imagePath = "images/{$type}/" . $imageName;
        file_put_contents(public_path($imagePath), base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData)));
        return $imagePath;
    }
}