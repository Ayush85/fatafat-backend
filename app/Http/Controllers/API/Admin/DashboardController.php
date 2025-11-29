<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        // Return dashboard statistics
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => 0,
                'total_orders' => 0,
                'total_products' => 0,
                'total_revenue' => 0,
            ],
            'message' => 'Dashboard data retrieved successfully'
        ]);
    }
}