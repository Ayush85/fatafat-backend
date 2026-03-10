<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\BannerModel;
use Illuminate\Http\Request;

/**
 * @group Banners
 *
 * Banner listing and detail endpoints.
 */
class BannerController extends Controller
{
    /**
     * List Banners
     *
     * @name List Banners
     */
    public function index(Request $request)
    {
        $query = BannerModel::where('status', 1)
            ->withCount('files')
            ->with(['defaultFile']);

        if ($request->has('slug')) {
            $query->where('slug', $request->slug);
        }

        $perPage = $request->input('per_page', 10);
        $banners = $query->paginate($perPage);

        return BannerResource::listResponse($banners);
    }

    /**
     * Get Banner By Slug
     *
     * @name Get Banner By Slug
     */
    public function showBySlug($slug)
    {
        $banner = BannerModel::where('slug', $slug)
            ->where('status', 1)
            ->with(['defaultFile', 'files'])
            ->first();

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        return BannerResource::detailResponse($banner);
    }
}
