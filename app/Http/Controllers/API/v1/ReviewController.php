<?php

namespace App\Http\Controllers\API\v1;

use App\Models\ProductReview;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Support\Facades\Validator;

/**
 * @group Reviews
 *
 * Product review read and write endpoints.
 */
class ReviewController extends Controller
{
    /**
     * List Product Reviews
     *
     * @name List Product Reviews
     */
    public function index(Request $request, $productId)
    {
        try {
            $product = Product::find($productId);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $query = ProductReview::where('product_id', $productId)
                ->where('status', ProductReview::STATUS_APPROVED)
                ->with('user')
                ->orderBy('created_at', 'desc');

            $perPage = $request->input('per_page', 20);
            $reviews = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductReviewResource::collection($reviews),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'last_page' => $reviews->lastPage(),
                    'average_rating' => $product->average_rating,
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create Product Review
     *
     * @name Create Product Review
     */
    public function store(Request $request, $productId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $product = Product::find($productId);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $review = ProductReview::create([
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'rating' => $request->rating,
                'review' => $request->review,
                'status' => ProductReview::STATUS_PENDING,
            ]);

            return $this->successResponse(
                new ProductReviewResource($review),
                'Review submitted successfully. It will be visible after approval.',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List Product Reviews (Legacy)
     *
     * @name List Product Reviews Legacy
     */
    public function getReviews($productId)
    {
        return $this->index(request(), $productId);
    }
}
