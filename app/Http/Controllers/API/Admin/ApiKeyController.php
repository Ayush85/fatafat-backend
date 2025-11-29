<?php

namespace App\Http\Controllers\API\Admin;

use App\Models\ApiKey;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

class ApiKeyController extends Controller
{
    public function index()
    {
        // Fetch all API keys
        $apiKeys = ApiKey::all();

        // Access all the routes using Route facade
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),                      // Route URI
                'methods' => implode(', ', $route->methods()), // HTTP methods (GET, POST, etc.)
                'middleware' => implode(', ', $route->middleware()), // Middleware used by the route
            ];
        });

        $routes = Route::getRoutes();
        $routeGroups = [];

        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'v1')) {
                // Extract route details
                $routeData = [
                    'uri'         => $route->uri(),
                    'name'        => $route->getName() ?? 'N/A',
                    'methods'     => implode('|', $route->methods()),
                    'middleware'  => implode(', ', $route->gatherMiddleware()) ?: 'None',
                    'action'      => $route->getActionName() ?: 'Closure',
                    'description' => $route->defaults['description'] ?? 'No description available',
                ];

                // Determine category based on URI structure
                if (str_contains($route->uri(), 'product')) {
                    $routeGroups['Products'][] = $routeData;
                } elseif (str_contains($route->uri(), 'categories')) {
                    $routeGroups['Categories'][] = $routeData;
                } elseif (str_contains($route->uri(), 'reviews')) {
                    $routeGroups['Reviews'][] = $routeData;
                } else {
                    $routeGroups['Others'][] = $routeData;
                }
            }
        }

        // Return the filtered results and API keys as JSON response
        return response()->json([
            'success' => true,
            'api_keys' => $apiKeys,
            'routes' => $routeGroups,
        ]);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'id' => 'nullable|exists:api_keys,id', // Check if ID exists in DB
            'host' => 'required|url',  // Ensures host is a valid URL
            'description' => 'required|string|max:255',  // Ensures description is a string
        ]);

        try {
            if ($request->has('id')) {
                // Find the existing API key record
                $apiKey = ApiKey::findOrFail($validated['id']);

                // Update only editable fields (avoid regenerating keys)
                $apiKey->update([
                    'host' => $validated['host'],
                    'description' => $validated['description'],
                ]);

                $message = 'API key updated successfully!';
            } else {
                // Create a new API key record (keys are auto-generated in the model)
                $apiKey = ApiKey::create([
                    'host' => $validated['host'],
                    'description' => $validated['description'],
                ]);

                $message = 'API key added successfully!';
            }

            // Return success response
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $apiKey
            ]);
        } catch (\Exception $e) {
            // Return error if something goes wrong
            return response()->json([
                'success' => false,
                'message' => 'Error processing request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $data = ApiKey::find($id);
        return response(["data" => $data], 200);
    }

    // Method to toggle the status of an API key (active/inactive)
    public function toggleStatus($id, Request $request)
    {
        // Validate the incoming data (ensure that the 'active' field is boolean)
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // Find the API key by its ID
        $apiKey = ApiKey::find($id);

        // If the API key is not found, return a not found response
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key not found.',
            ], 404);
        }

        // Update the 'active' status of the API key
        $apiKey->is_active = $validated['is_active'];
        $apiKey->save(); // Save the updated API key status

        // Return a success response
        return response()->json([
            'success' => true,
            'message' => 'API Key status updated successfully!',
            'api_key' => $apiKey,
        ]);
    }

    public function toggleMode($id, Request $request)
    {
        // Validate the incoming data (ensure that the 'active' field is boolean)
        $validated = $request->validate([
            'mode' => 'required|in:test,live',  // Corrected rule
        ]);

        // Find the API key by its ID
        $apiKey = ApiKey::find($id);

        // If the API key is not found, return a not found response
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API Key not found.',
            ], 404);
        }

        // Update the 'active' status of the API key
        $apiKey->mode = $validated['mode'];
        $apiKey->save(); // Save the updated API key status

        // Return a success response
        return response()->json([
            'success' => true,
            'message' => $apiKey->mode . " :mode activated",
            'api_key' => $apiKey,
        ]);
    }
}