<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\UserShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserShippingAddressResource; // Assuming we might need one, or return direct json

class UserShippingAddressController extends Controller
{
    public function index()
    {
        $addresses = UserShippingAddress::where('user_id', auth()->id())->get();
        return response()->json([
            'success' => true,
            'data' => $addresses,
            'message' => 'Shipping addresses retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
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
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
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
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
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
