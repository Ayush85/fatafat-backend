<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        try {
            $wishlists = $request->user()->wishlist()->with('product.media')->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $wishlists,
                'message' => 'Wishlist retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);

            $user = $request->user();

            // Check if already in wishlist
            $exists = $user->wishlist()->where('product_id', $request->product_id)->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product already in wishlist'
                ], 409);
            }

            $user->wishlist()->create([
                'product_id' => $request->product_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product added to wishlist successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $productId)
    {
        try {
            $deleted = $request->user()->wishlist()->where('product_id', $productId)->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found in wishlist'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product removed from wishlist successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
