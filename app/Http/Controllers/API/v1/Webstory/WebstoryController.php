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
            ->whereHas('defaultFile') // ensure a default image exists
            ->latest()
            ->paginate($perPage);

        $webstories = $blogs->getCollection()
            ->groupBy(function ($blog) {
                return optional($blog->category)->id;
            })
            ->map(function ($items) {
                $category = optional($items->first()->category);

                return [
                    'id' => $category->id ?? null,
                    'name' => $category->title ?? null,
                    'slug' => $category->slug ?? null,
                    'blogs' => $items->values()->map(function ($blog) {
                        $defaultFile = $blog->defaultFile->first();
                        return [
                            'id' => $blog->id,
                            'title' => $blog->title,
                            'slug' => $blog->slug,
                            'thumb' => [
                                'url' => $defaultFile?->url,
                                'alt_text' => $defaultFile?->pivot?->alt_text,
                            ],
                            'created_at' => $blog->created_at,
                        ];
                    }),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Webstories fetched successfully.',
            'data' => $webstories,
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
