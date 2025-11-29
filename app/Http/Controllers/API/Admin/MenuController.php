<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Menus retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Menu created successfully'
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Menu retrieved successfully'
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully'
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully'
        ]);
    }
}