<?php

namespace App\Http\Controllers\API\v1\EMI;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\EmiApplyCardRequest;
use App\Http\Requests\API\v1\EmiWithCitizenshipRequest;
use App\Http\Requests\API\v1\EmiWithCreditCardRequest;
use App\Models\EmiBankModel;
use App\Models\EmiRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\EmiService;
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
    public function __construct(private FileUploadService $fileUploadService, private EmiService $emiService) {}

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

        $product = Product::find($request->input('product_id'));
        if ($product && $product->price <= $request->input('down_payment')) {
            return response()->json([
                'success' => false,
                'message' => 'Down payment must be less than product price.',
            ], 422);
        }

        return match ($request->input('type')) {
            'credit_card' => $this->storeCreditCard($request, $product),
            'citizenship' => $this->storeCitizenship($request, $product),
            'apply_card' => $this->storeApplyCard($request, $product),
            default => response()->json([
                'success' => false,
                'message' => 'Invalid EMI request type provided.',
            ], 422),
        };
    }

    private function storeCreditCard(Request $request, Product $product)
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
            $financeAmount = $product->price - $validated['down_payment'];
            $emiData = $this->emiService->calculate(
                bankCode: $validated['credit_card']['card_provider'],
                duration: $validated['duration'],
                financeAmount: (float) $financeAmount,
            );

            if ($validated['variant_id']) {
                $productVariant = ProductVariant::where('id', $validated['variant_id'])->first();
            }

            // dd($productVariant);

            $emiRequest = EmiRequest::create([

                // general info
                'user_id' => auth()->user()->id,

                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['phone'],
                'address' => $validated['address'],
                'dob_ad' => $validated['dob_ad'],
                'dob_bs' => $validated['dob_bs'],
                'gender' => $validated['gender'],

                // emi product info
                'product_id' => $validated['product_id'],
                'product_variant' => $productVariant ? json_encode($productVariant->attributes) : null,
                'product_price' => $product->price,
                'emi_mode' => $validated['duration'],

                // financial info
                'emi_type' => $request->input('type'),
                'down_payment' => $validated['down_payment'],
                'finance_amount' => $emiData['finance_amount'],
                'interest_rate' => $emiData['interest_rate'],
                'emi_per_month' => $emiData['emi_per_month'],
                'status' => EmiRequest::STATUS_PENDING,

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

                $emiRequest->update([
                    'bank' => $emiBank->id,
                ]);

                $emiRequest->creditCard()->create([
                    'card_number' => $validated['credit_card']['card_number'],
                    'card_holder' => $validated['credit_card']['card_holder'],
                    'card_provider' => $emiBank->id,
                    'expiry_date' => $validated['credit_card']['expiry_date'],
                    'credit_limit' => $validated['credit_card']['credit_limit'],
                ]);
            }

            if ($validated['signature']) {
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
            $emiRequest->load('files', 'creditCard');

            return response()->json([
                'success' => true,
                'message' => 'EMI request submitted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollBack();
            return response([
                "error" => $th->getMessage(),
            ], 422);
        }
    }

    private function storeCitizenship(Request $request, Product $product)
    {
        $validator = Validator::make(
            $request->all(),
            (new EmiWithCitizenshipRequest)->rules()
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
            $financeAmount = $product->price - $validated['down_payment'];

            $emiData = $this->emiService->calculate(
                bankCode: $validated['bank'],
                duration: $validated['duration'],
                financeAmount: (float) $financeAmount,
            );

            if ($validated['variant_id']) {
                $productVariant = ProductVariant::where('id', $validated['variant_id'])->first();
            }

            $emiBank = EmiBankModel::where('bank_code', $validated['bank'])->first();

            $emiRequest = EmiRequest::create([

                // general info
                'user_id' => auth()->user()->id,

                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['phone'],
                'address' => $validated['address'],
                'dob_ad' => $validated['dob_ad'],
                'dob_bs' => $validated['dob_bs'],
                'gender' => $validated['gender'],
                'nid_number' => $validated['nid_number'],
                'bank' => $emiBank ? $emiBank->id : null,

                // emi product info
                'product_id' => $validated['product_id'],
                'product_variant' => $productVariant ? json_encode($productVariant->attributes) : null,
                'product_price' => $product->price,
                'emi_mode' => $validated['duration'],

                // financial info
                'emi_type' => $request->input('type'),
                'down_payment' => $validated['down_payment'],
                'finance_amount' => $emiData['finance_amount'],
                'interest_rate' => $emiData['interest_rate'],
                'emi_per_month' => $emiData['emi_per_month'],
                'status' => EmiRequest::STATUS_PENDING,

            ]);

            foreach ($validated['documents'] as $key => $doc) {
                $this->fileUploadService->uploadWithUsage(
                    file: $doc,
                    folder: 'emi-requests/' . $emiRequest->id . '/documents',
                    usageType: $emiRequest->getTable(),
                    usageId: $emiRequest->id,
                    title: $key,
                    altText: $key,
                );
            }

            if ($validated['signature']) {
                $this->fileUploadService->uploadSignatureForModel(
                    signature: $validated['signature'],
                    folder: 'emi-requests/' . $emiRequest->id . '/signature',
                    model: $emiRequest,
                    title: 'signature',
                    altText: 'signature',
                    usageMeta: ['tag' => 'signature']
                );
            }

            $guarantor = $emiRequest->guarantor()->create([
                'name' => $validated['guarantor']['name'],
                'phone' => $validated['guarantor']['phone'],
                'gender' => $validated['guarantor']['gender'],
                'marriage_status' => $validated['guarantor']['marriage_status'],
                'citizenship_number' => $validated['guarantor']['citizenship_number'],
            ]);

            foreach ($validated['guarantor']['documents'] as $key => $doc) {
                $this->fileUploadService->uploadWithUsage(
                    file: $doc,
                    folder: 'emi-requests/' . $emiRequest->id . '/guarantor-documents',
                    usageType: $guarantor->getTable(),
                    usageId: $guarantor->id,
                    title: $key,
                    altText: $key,
                );
            }

            DB::commit();
            $emiRequest->load('files', 'guarantor.files');

            return response()->json([
                'success' => true,
                'data' => $emiRequest,
                'message' => 'EMI request submitted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollBack();
            dd('error' . $th->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $validated,
            'message' => 'Validation successful',
        ], 200);
    }

    private function storeApplyCard(Request $request, Product $product)
    {
        $validator = Validator::make(
            $request->all(),
            (new EmiApplyCardRequest)->rules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();


        DB::beginTransaction();
        try {
            $financeAmount = $product->price - $validated['down_payment'];

            $emiData = $this->emiService->calculate(
                bankCode: $validated['bank']['code'],
                duration: $validated['duration'],
                financeAmount: (float) $financeAmount,
            );

            if ($validated['variant_id']) {
                $productVariant = ProductVariant::where('id', $validated['variant_id'])->first();
            }

            $emiRequest = EmiRequest::create([

                // general info
                'user_id' => auth()->user()->id,

                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['phone'],
                'address' => $validated['address'],
                'dob_ad' => $validated['dob_ad'],
                'dob_bs' => $validated['dob_bs'],
                'gender' => $validated['gender'],
                'nid_number' => $validated['nid_number'],
                'monthly_income' => $validated['monthly_salary'],

                // emi product info
                'product_id' => $validated['product_id'],
                'product_variant' => $productVariant ? json_encode($productVariant->attributes) : null,
                'product_price' => $product->price,
                'emi_mode' => $validated['duration'],

                // financial info
                'emi_type' => $request->input('type'),
                'down_payment' => $validated['down_payment'],
                'finance_amount' => $emiData['finance_amount'],
                'interest_rate' => $emiData['interest_rate'],
                'emi_per_month' => $emiData['emi_per_month'],
                'status' => EmiRequest::STATUS_PENDING,

            ]);

            foreach ($validated['documents'] as $key => $doc) {
                $this->fileUploadService->uploadWithUsage(
                    file: $doc,
                    folder: 'emi-requests/' . $emiRequest->id . '/documents',
                    usageType: $emiRequest->getTable(),
                    usageId: $emiRequest->id,
                    title: $key,
                    altText: $key,
                );
            }

            if ($validated['signature']) {
                $this->fileUploadService->uploadSignatureForModel(
                    signature: $validated['signature'],
                    folder: 'emi-requests/' . $emiRequest->id . '/signature',
                    model: $emiRequest,
                    title: 'signature',
                    altText: 'signature',
                    usageMeta: ['tag' => 'signature']
                );
            }

            $preferredBank = EmiBankModel::where('bank_code', $validated['bank']['code'])->first();
            if (!$preferredBank) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Selected bank is not eligible for EMI.',
                ], 422);
            }

            $emiRequest->update([
                'bank' => $preferredBank->id,
            ]);

            $emiRequest->preferredBank()->create([
                'bank_id' => $preferredBank->id,
                'account_number' => $validated['bank']['account_number'],
                'branch' => $validated['bank']['branch'],
            ]);


            DB::commit();
            $emiRequest->load('files', 'preferredBank');

            return response()->json([
                'success' => true,
                'data' => $emiRequest,
                'message' => 'EMI request submitted successfully.',
            ], 200);
        } catch (\Throwable $th) {
            // throw $th;
            DB::rollBack();
            dd('error' . $th->getMessage());
        }
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
