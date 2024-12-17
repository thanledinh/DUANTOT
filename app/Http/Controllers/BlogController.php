<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $blogs = Blog::orderBy('created_at', 'desc')->get();
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
            'content' => 'nullable|string',
            'image_data' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'status' => 'required|string|in:published,draft',
            'description' => 'nullable|string',
        ]);

        $slug = $validated['slug'] ?? $this->generateUniqueSlug($validated['title']);

        $imageUrl = null;
        if (!empty($validated['image_data'])) {
            $imageUrl = $this->handleImageUpload($validated['image_data'], 'blogs');
        }

        $blog = Blog::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'image_url' => $imageUrl,
            'status' => $validated['status'],
            'description' => $validated['description'],
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
            'image_data' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $id,
            'status' => 'nullable|string|in:published,draft',
            'description' => 'nullable|string',
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['slug'], $id);
        } elseif (isset($validated['title'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $id);
        }

        if (!empty($validated['image_data'])) {
            $validated['image_url'] = $this->handleImageUpload($validated['image_data'], 'blogs');
        }

        if (isset($validated['status'])) {
            $blog->status = $validated['status'];
        }

        if (array_key_exists('description', $validated)) {
            $blog->description = $validated['description'];
        }

        $blog->update($validated);

        return response()->json(['message' => 'Blog updated successfully', 'data' => $blog], 200);
    }

    private function generateUniqueSlug($title, $id = null)
    {
        $slug = Str::slug($title);
        $count = Blog::where('slug', 'LIKE', "{$slug}%")
            ->when($id, function ($query, $id) {
                return $query->where('id', '!=', $id);
            })
            ->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        if (!empty($blog->image_url)) {
            $blogImagePath = public_path( $blog->image_url);
            if (file_exists($blogImagePath)) {
                unlink($blogImagePath);
            }
        }
        
        $blog->delete();

        return response()->json(['message' => 'Blog deleted successfully'], 200);
    }

    //show blog theo slug
    public function showBySlug($slug)
    {
        $blog = Blog::where('slug', $slug)->first();
        return response()->json($blog, 200);
    }

    private function handleImageUpload($imageData, $folder)
    {
        try {
            // Tách phần type và dữ liệu base64
            list($type, $imageData) = explode(';', $imageData);
            list(, $imageData) = explode(',', $imageData);

            // Giải mã dữ liệu base64
            $imageData = base64_decode($imageData);

            // Tạo tên file hình ảnh với timestamp để đảm bảo tên file không trùng
            $imageName = time() . uniqid() . '.jpg'; // Bạn có thể thay đổi định dạng nếu cần
            $imageDirectory = public_path('images/' . $folder);
            $imagePath = $imageDirectory . '/' . $imageName;

            // Kiểm tra xem thư mục đã tồn tại chưa, nếu chưa thì tạo mới
            if (!file_exists($imageDirectory)) {
                mkdir($imageDirectory, 0755, true);
            }

            // Lưu hình ảnh vào thư mục
            if (file_put_contents($imagePath, $imageData) === false) {
                throw new \Exception('Failed to save image');
            }

            // Trả về đường dẫn hình ảnh để lưu vào cơ sở dữ liệu
            return 'images/' . $folder . '/' . $imageName;
        } catch (\Exception $e) {
            // Ghi log lỗi hoặc xử lý lỗi
            \Log::error('Image upload error: ' . $e->getMessage());
            return null;
        }
    }
}
