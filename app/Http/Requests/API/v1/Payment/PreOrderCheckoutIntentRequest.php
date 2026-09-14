<?php

namespace App\Http\Requests\API\v1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Shared validation for "start a pre-order deposit checkout" requests. Mirrors
 * CheckoutIntentRequest, but for a single product/deposit rather than a cart —
 * the order isn't created yet at this point, only once the gateway confirms
 * payment.
 */
abstract class PreOrderCheckoutIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',

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
