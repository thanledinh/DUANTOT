<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::all(); 
        return response()->json($blogs, 200);  
    }
    public function show($id)
    {
        $blog = Blog::find($id);  

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404); 
        }
        return response()->json($blog, 200); 
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|url',  // URL là optional
        ]);

        // Kiểm tra nếu người dùng là admin hoặc người đang đăng nhập
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);  // Chỉ admin mới có thể thêm
        }
        $blog = Blog::create([
            'user_id' => auth()->id(),  
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json(['message' => 'Blog created successfully', 'data' => $blog], 201); 
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::find($id); 
        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        // Kiểm tra quyền sở hữu (chỉ cho phép người tạo bài viết hoặc admin sửa)
        if ($blog->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);  // Người dùng không có quyền sửa bài viết này
        }
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'image_url' => 'nullable|url',
        ]);
        $blog->update($validated);

        return response()->json(['message' => 'Blog updated successfully', 'data' => $blog], 200);
    }

    public function destroy($id)
    {
        $blog = Blog::find($id);  

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        // Kiểm tra quyền sở hữu (chỉ cho phép người tạo bài viết hoặc admin xóa)
        if ($blog->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);  // Người dùng không có quyền xóa bài viết này
        }

        $blog->delete();  

        return response()->json(['message' => 'Blog deleted successfully'], 200);  
    }
}
