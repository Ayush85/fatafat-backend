<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Pages retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:pages',
            'content' => 'required|string',
            'status' => 'boolean'
        ]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Page created successfully'
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => null
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully'
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully'
        ]);
    }
}