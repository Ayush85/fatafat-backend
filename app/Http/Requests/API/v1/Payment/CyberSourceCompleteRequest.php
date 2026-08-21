<?php

namespace App\Http\Requests\API\v1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Unlike the other gateways' callback, Unified Checkout's completion step is
 * called by our own frontend (with the transient token the embedded JS
 * component produced), not by CyberSource redirecting the browser — so this
 * is a normal authenticated FormRequest, not a public callback route.
 */
class CyberSourceCompleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'transaction_uuid' => 'required|uuid',
            'transient_token' => 'required|string',
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
