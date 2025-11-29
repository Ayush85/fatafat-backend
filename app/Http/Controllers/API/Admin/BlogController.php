<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        // Return blogs list
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Blogs retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:blogs',
            'content' => 'required|string',
            'status' => 'boolean'
        ]);

        // Create blog
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Blog created successfully'
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
            'message' => 'Blog updated successfully'
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Blog deleted successfully'
        ]);
    }

    public function toggleBlogStatus($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Blog status updated successfully'
        ]);
    }

    public function toggleBlogFeature($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Blog feature status updated successfully'
        ]);
    }
}