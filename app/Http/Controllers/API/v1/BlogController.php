<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Blog;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Get list of published blogs with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $isFeatured = $request->get('featured', null);
        $categoryId = $request->get('category_id', null);

        $query = Blog::query()
            ->where('status', 1)  // Only published blogs
            ->orderBy('publish_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->with(['category', 'media']);

        if ($isFeatured !== null) {
            $query->where('is_featured', (bool) $isFeatured);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $blogs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => BlogResource::collection($blogs),
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'total' => $blogs->total(),
                'per_page' => $blogs->perPage(),
                'last_page' => $blogs->lastPage(),
            ],
            'message' => 'Blogs retrieved successfully'
        ]);
    }

    /**
     * Get single blog by slug with related data
     */
    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->where('status', 1)  // Only published blogs
            ->with(['category', 'media'])
            ->firstOrFail();

        // Get related blogs (similar to reference implementation)
        $relatedBlogs = Blog::where('status', 1)
            ->whereNot('id', $blog->id)
            ->where('category_id', $blog->category_id)
            ->latest('publish_date')
            ->take(5)
            ->with(['category', 'media'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => new BlogResource($blog),
            'related' => BlogResource::collection($relatedBlogs),
            'message' => 'Blog retrieved successfully'
        ]);
    }
}