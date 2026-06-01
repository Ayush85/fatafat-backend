<?php

namespace App\Http\Requests\API\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmiWithCreditCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Product
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],

            // Personal Info
            'full_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email'],
            'phone'            => ['required'],
            'dob_ad'           => ['required', 'date', 'before:today'],
            'dob_bs'           => ['required', 'date'],
            'gender'           => ['required', 'in:male,female,other'],
            'marital_status'  => ['required'],
            'national_id'      => ['required'],
            'address'          => ['required'],
            'down_payment' => ['required'],
            // 'loan_amount' => ['nullable'],
            'duration' => ['required'],

            // Credit Card (ONLY)
            'credit_card.card_number'   => ['required'],
            'credit_card.card_holder'   => ['required'],
            'credit_card.card_provider' => ['required'],
            'credit_card.expiry_date'   => ['required', 'date_format:m/y'],
            'credit_card.credit_limit'  => ['required', 'numeric'],

            // Signature & consent
            'signature'    => ['nullable', 'file'],
            'agreed_terms' => ['required', 'boolean'],
            'documents' => ['nullable', 'array'],
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
