<?php

namespace App\Http\Controllers\API\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jed\Ecommerce\Cart\UserShippingAddress;
use Illuminate\Support\Facades\Validator;

class ShippingAddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $addresses = UserShippingAddress::where('user_id', auth()->id())->get();
        return response()->json(['data' => $addresses]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();
        $data['country'] = $data['country'] ?? 'Nepal';

        // Handle default address logic if needed
        if ($request->is_default) {
            UserShippingAddress::where('user_id', auth()->id())->update(['is_default' => 0]);
        }

        $address = UserShippingAddress::create($data);

        return response()->json(['data' => $address, 'message' => 'Shipping address created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        return response()->json(['data' => $address]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'contact_number' => 'sometimes|required|string|max:20',
            'province' => 'sometimes|required|string|max:255',
            'district' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('is_default') && $request->is_default) {
            UserShippingAddress::where('user_id', auth()->id())->where('id', '!=', $id)->update(['is_default' => 0]);
        }

        $address->update($request->all());

        return response()->json(['data' => $address, 'message' => 'Shipping address updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $address = UserShippingAddress::where('user_id', auth()->id())->find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'Shipping address deleted successfully.']);
    }
}
