<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductGiftController extends Controller
{
    public function index(int $productId)
    {
        $product = ProductModel::findOrFail($productId);

        $gifts = DB::table('product_gifts')
            ->join('products', 'products.id', '=', 'product_gifts.gift_product_id')
            ->where('product_gifts.product_id', $product->id)
            ->whereNull('products.deleted_at')
            ->select(
                'product_gifts.id',
                'product_gifts.status',
                'products.id as product_id',
                'products.name',
                'products.slug',
                'products.price',
                'products.quantity',
            )
            ->paginate(request('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $gifts->items(),
            'meta' => [
                'current_page' => $gifts->currentPage(),
                'per_page'     => $gifts->perPage(),
                'total'        => $gifts->total(),
                'last_page'    => $gifts->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $productId)
    {
        $request->validate([
            'gift_product_ids'   => 'required|array|min:1',
            'gift_product_ids.*' => 'required|integer|exists:products,id',
        ]);

        $product = ProductModel::findOrFail($productId);

        $rows = collect($request->gift_product_ids)
            ->reject(fn ($id) => $id === $productId)
            ->unique()
            ->map(fn ($id) => [
                'product_id'      => $product->id,
                'gift_product_id' => $id,
                'status'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ])
            ->values()
            ->all();

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No valid gift products provided.'], 422);
        }

        DB::table('product_gifts')->upsert(
            $rows,
            ['product_id', 'gift_product_id'],
            ['status', 'updated_at']
        );

        return response()->json(['success' => true, 'message' => 'Gift items added.']);
    }

    public function destroy(int $productId, int $giftProductId)
    {
        $deleted = DB::table('product_gifts')
            ->where('product_id', $productId)
            ->where('gift_product_id', $giftProductId)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Gift item not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Gift item removed.']);
    }

    public function updateStatus(Request $request, int $productId, int $giftProductId)
    {
        $request->validate(['status' => 'required|boolean']);

        $updated = DB::table('product_gifts')
            ->where('product_id', $productId)
            ->where('gift_product_id', $giftProductId)
            ->update(['status' => $request->boolean('status') ? 1 : 0, 'updated_at' => now()]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Gift item not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }
}
