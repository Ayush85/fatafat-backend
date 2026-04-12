<?php

namespace App\Http\Controllers\API\v1\Webstory;

use App\Http\Controllers\Controller;
use App\Models\BlogModel;
use Illuminate\Http\Request;

/**
 * @group Webstory
 *
 * Webstory listing endpoints.
 */
class WebstoryController extends Controller
{
    /**
     * Webstory List
     *
     * @name Webstory List
     */
    public function getWebStories(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);

        $blogs = BlogModel::with(['category', 'defaultFile'])
            ->whereNotNull('title')
            ->whereHas('category', function ($query) {
                $query->whereNotNull('title');
            })
            ->whereHas('defaultFile') // ensure image exists
            ->latest()
            ->paginate($perPage);

        $webstories = $blogs->getCollection()
            ->groupBy(function ($blog) {
                return optional($blog->category)->slug ?? 'uncategorized';
            })
            ->map(function ($items) {

                return $items->values()->map(function ($blog) {

                    $category = optional($blog->category);
                    $defaultFile = $blog->defaultFile->first();

                    return [
                        'id' => $blog->id,
                        'title' => $blog->title,
                        'slug' => $blog->slug,
                        'short_desc' => $blog->short_desc ?? null,

                        'thumb' => [
                            'url' => $defaultFile?->url,
                            'alt_text' => $defaultFile?->pivot?->alt_text ?? $blog->title,
                        ],

                        'publish_date' => $blog->publish_date ?? $blog->created_at,
                        'author' => $blog->author ?? null,

                        'category' => [
                            'name' => $category->title ?? null,
                            'slug' => $category->slug ?? null,
                        ],
                    ];
                });
            });

        return response()->json([
            'success' => true,
            'message' => 'Webstories fetched successfully.',
            'data' => $webstories, // 🔑 keyed by category slug
            'meta' => [
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'from' => $blogs->firstItem(),
                'to' => $blogs->lastItem(),
            ],
        ]);
    }
}