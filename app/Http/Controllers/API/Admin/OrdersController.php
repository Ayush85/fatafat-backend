<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function getOrders(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Orders retrieved successfully'
        ]);
    }

    public function getOrderDetail($id)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Order detail retrieved successfully'
        ]);
    }

    public function updateOrderStatus($id, Request $request)
    {
        $request->validate([
            'status' => 'required|integer|min:0|max:5',
            'notes' => 'nullable|string'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully'
        ]);
    }

    public function getPreOrders()
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Pre-orders retrieved successfully'
        ]);
    }

    public function getPreOrderDetail($id)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Pre-order detail retrieved successfully'
        ]);
    }

    public function updateOrderItemWarrantySerial($orderItemId, Request $request)
    {
        $request->validate([
            'warranty_serial' => 'required|string|max:255'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Warranty serial updated successfully'
        ]);
    }
}