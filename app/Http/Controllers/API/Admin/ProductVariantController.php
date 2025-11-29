<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index($productId, Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Product variants retrieved successfully'
        ]);
    }

    public function store($productId, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:product_variants',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'attributes' => 'required|array'
        ]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Product variant created successfully'
        ], 201);
    }

    public function show($productId, $id)
    {
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }

    public function update($productId, Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Product variant updated successfully'
        ]);
    }

    public function destroy($productId, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Product variant deleted successfully'
        ]);
    }
}