<?php

namespace App\Http\Controllers\API\v1\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\BlogCategoryModel;
use App\Models\BlogModel;
use Illuminate\Http\Request;

/**
 * @group Blogs
 *
 * Blog listing and detail endpoints.
 */
class BlogController1 extends Controller
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

        $query = BlogModel::query()
            ->where('status', 1)
            ->with(['category', 'media']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($categorySlug) {
            $category = BlogCategoryModel::where('slug', $categorySlug)
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

        if ($ordering) {
            $direction = 'asc';
            if (str_contains(strtolower($ordering), 'desc') || str_starts_with($ordering, '-')) {
                $direction = 'desc';
            }

            if (str_contains($ordering, 'crated_at') || str_contains($ordering, 'created_at')) {
                $query->orderBy('created_at', $direction);
            } else {
                $query->orderBy('publish_date', 'desc');
            }
        } else {
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
            'message' => 'Blogs retrieved successfully',
        ]);
    }

    /**
     * Get Blog By Slug
     *
     * @name Get Blog By Slug
     */
    public function show($slug)
    {
        $blog = BlogModel::where('slug', $slug)
            ->where('status', 1)
            ->with(['category', 'media'])
            ->firstOrFail();

        $relatedBlogs = BlogModel::where('status', 1)
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
            'message' => 'Blog retrieved successfully',
        ]);
    }
}
