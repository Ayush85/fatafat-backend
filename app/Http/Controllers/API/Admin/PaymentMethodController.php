<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Payment methods retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Payment method created successfully'
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Payment method retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully'
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully'
        ]);
    }
}