<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\UserShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Users
 *
 * User shipping address management endpoints.
 */
class UserShippingAddressController extends Controller
{
    /**
     * List Shipping Addresses
     *
     * @name List Shipping Addresses
     */
    public function index()
    {
        $addresses = UserShippingAddress::where('user_id', auth()->id())->get();
        return response()->json([
            'success' => true,
            'data' => $addresses,
            'message' => 'Shipping addresses retrieved successfully'
        ]);
    }

    /**
     * Create Shipping Address
     *
     * @name Create Shipping Address
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            // 'email' => 'required|email|max:255', // Removed as per schema
            'landmark' => 'required|string|max:255', // Was address
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100', // New
            'province' => 'required|string|max:100', // Was state
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // If setting as default, unset other defaults
        if ($request->is_default) {
            UserShippingAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address = UserShippingAddress::create([
            'user_id' => auth()->id(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'contact_number' => $request->contact_number,
            // 'email' => $request->email,
            'landmark' => $request->landmark,
            'city' => $request->city,
            'district' => $request->district,
            'province' => $request->province,
            'country' => $request->country ?? 'Nepal',
            'is_default' => $request->is_default ?? false,
        ]);

        // If it's the first address, make it default
        if (UserShippingAddress::where('user_id', auth()->id())->count() === 1) {
            $address->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Shipping address saved successfully'
        ], 201);
    }

    /**
     * Get Shipping Address
     *
     * @name Get Shipping Address
     */
    public function show($id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Shipping address retrieved successfully'
        ]);
    }

    /**
     * Update Shipping Address
     *
     * @name Update Shipping Address
     */
    public function update(Request $request, $id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'landmark' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->is_default) {
            UserShippingAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Shipping address updated successfully'
        ]);
    }

    /**
     * Delete Shipping Address
     *
     * @name Delete Shipping Address
     */
    public function destroy($id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found'
            ], 404);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping address deleted successfully'
        ]);
    }
}
