<?php

namespace App\Http\Requests\API\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication is enforced by middleware; allow validated users through.
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

            'payment.type' => 'required|string|in:cash_on_delivery,esewa,khalti,nica',
            'payment.promo_code' => 'nullable|string|max:50',
            'payment.total' => 'required|numeric|min:0',

            'cart_id' => 'required|integer|exists:carts,id',
            'shipping_cost' => 'nullable|numeric|min:0',

            'recipient.type' => 'required|string|in:self,gift',
            'recipient.phone' => 'required|string|max:20',
            'recipient.name' => 'required|string|max:150',
            'recipient.photos.sender' => 'nullable|string',
            'recipient.photos.receiver' => 'nullable|string',
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
