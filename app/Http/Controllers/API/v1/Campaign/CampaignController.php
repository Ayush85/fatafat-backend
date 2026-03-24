<?php

namespace App\Http\Controllers\API\v1\Campaign;


use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\DiscountCampaignModel;
use Illuminate\Http\Request;

/**
 * @group Campaign
 *
 * Campaign listing and detail endpoints.
 */
class CampaignController extends Controller
{
    /**
     * Campaign List
     *
     * @name Campaign List
     *
     */
    public function campaignList(Request $request)
    {
      $now = now();
      $query = DiscountCampaignModel::with(['defaultFile'])
            ->withCount('products')
            ->where('is_published', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->orderByDesc('created_at');


        $data = $query->get();
        return response()->json([
            'success' => true,
            'data' => $data->map(fn($campaign) => (new CampaignResource($campaign))->listResponse()),
        ], 200);
    }

     /**
     * Campaign Detail
     *
     * @name Campaign Detail
     *
     */
    public function getCampaign(Request $request, $slug){
        $data = DiscountCampaignModel::with([
            'files',
            'defaultFile',
            'products.product.defaultFile',
            'products.product.brand',
            'products.product.categories',
        ])
            ->where('slug', $slug)
            ->firstOrFail();
        return response()->json([
            'success' => true,
            'data' => (new CampaignResource($data))->detailResponse($request),
        ], 200);
    }

}
