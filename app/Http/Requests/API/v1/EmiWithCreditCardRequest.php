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

            // Credit Card (ONLY)
            'credit_card.card_number'   => ['required'],
            'credit_card.card_holder'   => ['required'],
            'credit_card.card_provider' => ['required'],
            'credit_card.expiry_date'   => ['required', 'date_format:m/y'],
            'credit_card.credit_limit'  => ['required', 'numeric'],

            // Documents (strict for credit card flow)
            'documents.citizenship_front' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf'],
            'documents.citizenship_back'  => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf'],
            'documents.pp_photo'          => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf'],

            // Signature & consent
            'signature'    => ['nullable', 'file'],
            'agreed_terms' => ['required', 'boolean'],
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
