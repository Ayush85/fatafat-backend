<?php

namespace App\Http\Requests\API\v1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Shared validation for "start an online-gateway checkout" requests. Mirrors
 * OrderStoreRequest (cart/shipping/recipient) since the order isn't created
 * yet at this point — the gateway controller builds a checkout snapshot from
 * this instead of an existing order row.
 */
abstract class CheckoutIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'shipping_address.id' => 'nullable|integer|exists:user_shipping_addresses,id',
            'shipping_address.label' => 'nullable|string|max:100',
            'shipping_address.landmark' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.district' => 'required|string|max:100',
            'shipping_address.province' => 'required|string|max:100',
            'shipping_address.country' => 'required|string|max:100',
            'shipping_address.is_default' => 'nullable|boolean',
            'shipping_address.geo.lat' => 'nullable|numeric',
            'shipping_address.geo.lng' => 'nullable|numeric',

            'cart_id' => 'required|integer|exists:carts,id',
            'shipping_cost' => 'nullable|numeric|min:0',

            'recipient.type' => 'required|string|in:self,gift',
            'recipient.phone' => 'required|string|max:20',
            'recipient.name' => 'required|string|max:150',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
