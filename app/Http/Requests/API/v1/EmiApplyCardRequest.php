<?php

namespace App\Http\Requests\API\v1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmiApplyCardRequest extends FormRequest
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

            // Finance
            'down_payment'  => ['nullable', 'numeric', 'min:0'],
            'duration'      => ['required', 'integer', 'min:1'],

            // Personal Info
            'full_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email'],
            'phone'            => ['required'],
            'dob_ad'           => ['required', 'date', 'before:today'],
            'dob_bs'           => ['required', 'date'],
            'nid_number'      => ['required'],
            'gender'           => ['required', 'in:male,female,other'],
            'marriage_status'  => ['required', 'in:single,married'],
            'address'          => ['required'],

            // Salary
            'monthly_salary'    => ['required', 'numeric', 'min:0'],
            
            // Bank
            'bank.code'           => ['required', 'string'],
            'bank.account_number' => ['required', 'string'],
            'bank.branch'         => ['required', 'string'],

            
            // Documents (apply card flow)
            'documents.salary_statement' => ['nullable', 'file', 'mimes:pdf'],
            'documents.citizenship_front' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp'],
            'documents.citizenship_back'  => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp'],
            'documents.pp_photo'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp'],

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
