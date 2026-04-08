<?php

namespace App\Http\Controllers\API\v1\EMI;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\EmiApplyCardRequest;
use App\Http\Requests\API\v1\EmiWithCitizenshipRequest;
use App\Http\Requests\API\v1\EmiWithCreditCardRequest;
use App\Models\EmiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group EMI Requests
 *
 * EMI request submission endpoints.
 */
class EmiRequestStoreController extends Controller
{
    /**
     * Submit EMI Request
     *
     * @name Submit EMI Request
     */
    public function store(Request $request)
    {
        $typeValidator = Validator::make($request->all(), [
            'type' => ['required', 'in:credit_card,citizenship,apply_card'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        if ($typeValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $typeValidator->errors(),
            ], 422);
        }

        return match ($request->input('type')) {
            'credit_card' => $this->storeCreditCard($request),
            'citizenship' => $this->storeCitizenship($request),
            'apply_card' => $this->storeApplyCard($request),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid EMI request type provided.',
            ], 422),
        };
    }

    private function storeCreditCard(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            (new EmiWithCreditCardRequest)->rules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

         return response()->json([
            'success' => true,
            'data' => $validated,
            'message' => 'Validation successful',
        ], 200);

        return $this->persistRequest($request, $validated);
    }

    private function storeCitizenship(Request $request)
    {
         $validator = Validator::make(
            $request->all(),
            (new EmiWithCitizenshipRequest())->rules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

         return response()->json([
            'success' => true,
            'data' => $validated,
            'message' => 'Validation successful',
        ], 200);
        return $this->persistRequest($request, $validated);
    }

    private function storeApplyCard(Request $request)
    {
       $validator = Validator::make(
            $request->all(),
            (new EmiApplyCardRequest())->rules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // dd($validated);
        return response()->json([
            'success' => true,
            'data' => $validated,
            'message' => 'Validation successful',
        ], 200);
        return $this->persistRequest($request, $validated);
    }

    private function persistRequest(Request $request, array $validated)
    {
        try {
            $data = $validated;

            // Handle File Uploads
            $uploadPath = 'emi/requests';
            $fileMap = [
                'salary_certificate' => 'salary_certificate',
                'citizenship' => 'citizenship',
                'photo' => 'photo',
                'bank_statement' => 'bank_statement',
                'documents.citizenship_front' => 'citizenship_front',
                'documents.citizenship_back' => 'citizenship_back',
                'documents.pp_photo' => 'pp_photo',
                'signature' => 'signature',
            ];

            foreach ($fileMap as $inputKey => $storeKey) {
                if ($request->hasFile($inputKey)) {
                    $data[$storeKey] = $request->file($inputKey)->store($uploadPath, 'public');
                }
            }

            $data['user_id'] = auth()->id();
            $data['status'] = 0; // Pending

            $emiRequest = EmiRequest::create($data);

            return response()->json([
                'success' => true,
                'data' => $emiRequest,
                'message' => 'EMI Request submitted successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: '.$e->getMessage(),
            ], 500);
        }
    }
}
