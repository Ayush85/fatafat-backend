<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user('sanctum')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user has vendor role or is a vendor
        $user = $request->user('sanctum');
        
        // Check if it's a vendor model or has vendor role
        if ($user instanceof \App\Models\Vendor) {
            return $next($request);
        }

        if (isset($user->role) && $user->role === 'vendor') {
            return $next($request);
        }

        if (isset($user->type) && $user->type === 'vendor') {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Vendor access required.'
        ], 403);
    }
}
