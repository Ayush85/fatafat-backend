<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Page;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Illuminate\Http\Request;

/**
 * @group Pages
 *
 * Static page content endpoints.
 */
class PageController extends Controller
{
    /**
     * Get Page By Slug
     *
     * @name Get Page By Slug
     */
    public function show($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new PageResource($page),
            'message' => 'Page retrieved successfully'
        ]);
    }
}
