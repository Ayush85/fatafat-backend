<?php

namespace App\Http\Controllers\API\v1\Blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogCategoryResource;
use App\Models\BlogCategoryModel;

/**
 * @group Blogs
 *
 * Blog category endpoints.
 */
class BlogCategoryController extends Controller
{
    /**
     * List Blog Categories
     *
     * @name List Blog Categories
     */
    public function index()
    {
        $categories = BlogCategoryModel::where('status', 1)
            ->with('defaultFile')
            ->withCount('blogs')
            ->get();

        return response()->json([
            'success' => true,
            'data' => BlogCategoryResource::collection($categories),
            'message' => 'Blog categories retrieved successfully',
        ]);
    }
}
