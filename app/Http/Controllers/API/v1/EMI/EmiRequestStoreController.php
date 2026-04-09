<?php

namespace App\Http\Controllers\API\v1\EMI;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\EmiApplyCardRequest;
use App\Http\Requests\API\v1\EmiWithCitizenshipRequest;
use App\Http\Requests\API\v1\EmiWithCreditCardRequest;
use App\Models\EmiBankModel;
use App\Models\EmiRequest;
use App\Models\Product;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * @group EMI Requests
 *
 * EMI request submission endpoints.
 */
class EmiRequestStoreController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

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

        // dd($validated);
        DB::beginTransaction();
        try {
            $product = Product::find($validated['product_id']);
            $emiRequest = EmiRequest::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['phone'],
                'address' => $validated['address'],
                'dob_ad' => $validated['dob_ad'],
                'dob_bs' => $validated['dob_bs'],
                'gender'    => $validated['gender'],

                'down_payment' => $validated['down_payment'],
                'product_id' => $validated['product_id'],
                'emi_mode' => $validated['duration'],
                'credit_card' => 1,
                'bank' => 11,
                'finance_amount' => $validated['loan_amount'],
                'emi_per_month' => 1000,
                'user_id' => auth()->user()->id,
                'product_price' => $product->price,
            ]);
            // dd($emiRequest);
            $storedFiles = [];
            foreach ($validated['documents'] as $key => $doc) {

                $storedFiles[] = $this->fileUploadService->uploadWithUsage(
                    file: $doc,
                    folder: 'emi-requests/' . $emiRequest->id . '/documents',
                    usageType: $emiRequest->getTable(),
                    usageId: $emiRequest->id,
                    title: $key,
                    altText: $key,
                );
            }

            $emiBank = EmiBankModel::where('bank_code', $validated['credit_card']['card_provider'])->first();
            if ($emiBank) {
                $emiRequest->creditCard()->create([
                    'card_number' => $validated['credit_card']['card_number'],
                    'card_holder' => $validated['credit_card']['card_holder'],
                    'card_provider' => $emiBank->id,
                    'expiry_date' => $validated['credit_card']['expiry_date'],
                    'credit_limit' => $validated['credit_card']['credit_limit'],
                ]);
            }

            if($validated['signature']){
                $this->fileUploadService->uploadSignatureForModel(
                    signature: $validated['signature'],
                    folder: 'emi-requests/' . $emiRequest->id . '/signature',
                    model: $emiRequest,
                    title: 'signature',
                    altText: 'signature',
                    usageMeta: ['tag' => 'signature']
                );
            }

            DB::commit();
            $emiRequest->load('files','creditCard');
            return response()->json([
                'success' => true,
                'data' => $emiRequest,
                'message' => 'Validation successful',
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            dd('error' . $th->getMessage());
        }

        // return $this->persistRequest($request, $validated);
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
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
