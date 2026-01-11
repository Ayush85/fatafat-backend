<?php

namespace App\Http\Controllers\API\v1;

use App\Models\ProductBrand;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBrandResource;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ProductBrand::where('status', 1);

            if ($request->filled('paginate') && $request->paginate == 'false') {
                $brands = $query->orderBy('name', 'asc')->get();
                return $this->successResponse(ProductBrandResource::collection($brands));
            }

            $perPage = $request->input('per_page', 20);
            $brands = $query->orderBy('name', 'asc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductBrandResource::collection($brands),
                'meta' => [
                    'current_page' => $brands->currentPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'last_page' => $brands->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $brand = ProductBrand::find($id);

            if (!$brand) {
                return $this->errorResponse('Brand not found', 404);
            }

            return $this->successResponse(new ProductBrandResource($brand));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
