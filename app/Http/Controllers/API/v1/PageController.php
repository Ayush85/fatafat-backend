<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Page;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use Illuminate\Http\Request;

class PageController extends Controller
{
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
