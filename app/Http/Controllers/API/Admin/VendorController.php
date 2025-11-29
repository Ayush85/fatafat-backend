<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vendors = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $vendors,
            'meta' => [
                'current_page' => $vendors->currentPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
                'last_page' => $vendors->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendors',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $vendor = Vendor::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $vendor,
            'message' => 'Vendor created successfully'
        ], 201);
    }

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendors,email,' . $id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $vendor->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $vendor,
            'message' => 'Vendor updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor deleted successfully'
        ]);
    }

    public function getVendorsDropdown()
    {
        $vendors = Vendor::select('id', 'name')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vendors
        ]);
    }
}