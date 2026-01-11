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

        $banners = $query->get();

        return response()->json([
            'success' => true,
            'data' => BannerResource::collection($banners),
            'message' => 'Banners retrieved successfully'
        ]);
    }
}
