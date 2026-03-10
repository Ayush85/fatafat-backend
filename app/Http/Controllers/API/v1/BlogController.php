<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Blog;
use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use Illuminate\Http\Request;

/**
 * @group Blogs
 *
 * Blog listing, categories, and detail endpoints.
 */
class BlogController extends Controller
{
    /**
     * List Blogs
     *
     * @name List Blogs
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $isFeatured = $request->get('featured', null);
        $categoryId = $request->get('category_id', null);
        $categorySlug = $request->get('category', null);
        $author = $request->get('author', null);
        $createdAt = $request->get('created_at', null);
        $ordering = $request->get('ordering', null);

        $query = Blog::query()
            ->where('status', 1)  // Only published blogs
            ->with(['category', 'media']);

        // Filter by Category ID
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Filter by Category Slug or Name
        if ($categorySlug) {
            $category = \App\Models\BlogCategory::where('slug', $categorySlug)
                ->orWhere('title', $categorySlug)
                ->first();

            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        if ($isFeatured !== null) {
            $query->where('is_featured', (bool) $isFeatured);
        }

        if ($author) {
            $query->where('author', 'like', "%{$author}%");
        }

        if ($createdAt) {
            $query->whereDate('created_at', $createdAt);
        }

        // Handle Ordering
        if ($ordering) {
            $direction = 'asc';
            if (str_contains(strtolower($ordering), 'desc') || str_starts_with($ordering, '-')) {
                $direction = 'desc';
            }

            // Handle common typos or specific fields
            if (str_contains($ordering, 'crated_at') || str_contains($ordering, 'created_at')) {
                $query->orderBy('created_at', $direction);
            } else {
                // Default fallback if ordering param exists but doesn't match known fields
                $query->orderBy('publish_date', 'desc');
            }
        } else {
            // Default ordering
            $query->orderBy('publish_date', 'desc')
                ->orderBy('created_at', 'desc');
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
     * List Blog Categories
     *
     * @name List Blog Categories
     */
    public function categories()
    {
        $categories = \App\Models\BlogCategory::where('status', 1)
            ->withCount('blogs')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Blog categories retrieved successfully'
        ]);
    }

    /**
     * Get Blog By Slug
     *
     * @name Get Blog By Slug
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
