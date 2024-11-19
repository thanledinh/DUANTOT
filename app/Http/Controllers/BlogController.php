<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    //show blog cho user
    public function showBlogsUser()
    {
        $blogs = Blog::orderBy('created_at', 'desc')->get();
        return response()->json($blogs, 200);
    }
    
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

        $blog->delete();

        return response()->json(['message' => 'Blog deleted successfully'], 200);
    }
}
