<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Banner;
use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $query = Banner::where('status', 1)
            ->with(['images']);

        if ($request->has('slug')) {
            $query->where('slug', $request->slug);
        }

        // Support pagination
        if ($request->filled('paginate') && $request->paginate == 'false') {
            $banners = $query->get();
            return response()->json([
                'success' => true,
                'data' => BannerResource::collection($banners),
                'message' => 'Banners retrieved successfully'
            ]);
        }

        $perPage = $request->input('per_page', 10);
        $banners = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => BannerResource::collection($banners),
            'meta' => [
                'current_page' => $banners->currentPage(),
                'per_page' => $banners->perPage(),
                'total' => $banners->total(),
                'last_page' => $banners->lastPage(),
            ],
            'message' => 'Banners retrieved successfully'
        ]);
    }
    public function showBySlug($slug)
    {
        $banner = Banner::where('slug', $slug)
            ->where('status', 1)
            ->with(['images'])
            ->first();

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BannerResource($banner),
            'message' => 'Banner retrieved successfully'
        ]);
    }
}
