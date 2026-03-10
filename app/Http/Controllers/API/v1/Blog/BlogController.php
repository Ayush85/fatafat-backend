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
class BlogController extends Controller
{
    /**
     * Blog List
     *
     * @name Blog List
     *
     * @queryParam category string Filter by category slug or title. Example: news
     * @queryParam category_id integer Filter by category ID. Example: 1
     * @queryParam author string Filter by author name using partial match. Example: Jiban Shrestha
     * @queryParam featured boolean Filter by featured blogs. Example: 1
     * @queryParam created_at string Filter by created date in YYYY-MM-DD format. Example: 2026-03-10
     * @queryParam published_date string Filter by publish date in YYYY-MM-DD format. Example: 2026-03-10
     * @queryParam sort string Sort by publish date. Use `asc`, `desc`, or a leading `-` for descending. Example: desc
     * @queryParam page integer The page number. Example: 1
     * @queryParam per_page integer The number of items per page. Example: 20
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $isFeatured = $request->get('featured', null);
        $categoryId = $request->get('category_id', null);
        $categorySlug = $request->get('category', null);
        $author = $request->get('author', null);
        $createdAt = $request->get('created_at', null);
        $publishedDate = $request->get('published_date', null);
        $sort = $request->get('sort', null);

        $query = BlogModel::query()
            ->where('status', 1)
            ->with(['category', 'defaultFile']);

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

        if ($publishedDate) {
            $query->whereDate('publish_date', $publishedDate);
        }

        if ($sort) {
            $direction = 'asc';
            if (str_contains(strtolower($sort), 'desc') || str_starts_with($sort, '-')) {
                $direction = 'desc';
            }

            $query->orderBy('publish_date', $direction);
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
     * Blog Detail By Slug
     *
     * @name Blog Detail By Slug
     */
    public function show($slug)
    {
        $blog = BlogModel::where('slug', $slug)
            ->where('status', 1)
            ->with(['category.defaultFile', 'defaultFile'])
            ->firstOrFail();

        $relatedBlogs = BlogModel::where('status', 1)
            ->whereNot('id', $blog->id)
            ->where('category_id', $blog->category_id)
            ->latest('publish_date')
            ->take(5)
            ->with(['category', 'defaultFile'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => new BlogResource($blog),
            'related' => $relatedBlogs->map(fn ($relatedBlog) => (new BlogResource($relatedBlog))->toListArray())->values(),
            'message' => 'Blog retrieved successfully',
        ]);
    }
}
