<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\UserShippingAddress;
use App\Http\Resources\UserShippingAddressResource;
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
        $addresses = UserShippingAddress::where('user_id', auth()->id())->orderByDesc('id')->get();
        return response()->json([
            'success' => true,
            'data' => UserShippingAddressResource::collection($addresses),
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
            'full_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
          
            'label' => 'nullable|string|max:255',
            'landmark' => 'required|string|max:255', // Was address
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100', // New
            'province' => 'required|string|max:100', // Was state
            'country' => 'nullable|string|max:100',
          
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!empty($request->full_name)) {
            $nameParts = explode(' ', trim($request->full_name), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        } else {
            $nameParts = explode(' ', trim($user->name ?? ''), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }

        $contactNumber = $request->contact_number ?: $user->contact_number;

        if (empty($firstName) || empty($lastName) || empty($contactNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'Full name (first and last) and contact number are required either in request or user profile.',
            ], 422);
        }

        // If setting as default, unset other defaults
        if ($request->is_default) {
            UserShippingAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = UserShippingAddress::create([
            'user_id' => $user->id,
            
            'first_name' => $firstName,
            'last_name' => $lastName,
            'contact_number' => $contactNumber,
            // 'email' => $request->email,
            
            'label' => $request->label,
            'landmark' => $request->landmark,
            'city' => $request->city,
            'district' => $request->district,
            'province' => $request->province,
            'country' => $request->country ?? 'Nepal',
            
            'lat' => $request->lat,
            'lng' => $request->lng,
            'is_default' => $request->is_default ?? false,
       
        ]);

        // If it's the first address, make it default
        if (UserShippingAddress::where('user_id', $user->id)->count() === 1) {
            $address->update(['is_default' => true]);
        }

        return response()->json([
            'success' => true,
            'data' => new UserShippingAddressResource($address),
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
            'data' => new UserShippingAddressResource($address),
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
            'full_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'label' => 'nullable|string|max:255',
            'landmark' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->except(['full_name']);

        if ($request->has('full_name') && !empty($request->full_name)) {
            $nameParts = explode(' ', trim($request->full_name), 2);
            $updateData['first_name'] = $nameParts[0] ?? '';
            $updateData['last_name'] = $nameParts[1] ?? '';
        }

        if ($request->is_default) {
            UserShippingAddress::where('user_id', auth()->id())
                               ->where('id', '!=', $id)
                               ->update(['is_default' => false]);
        }

        $address->update($updateData);

        return response()->json([
            'success' => true,
            'data' => new UserShippingAddressResource($address),
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
