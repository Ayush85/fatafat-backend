<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\EmiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group EMI Requests
 *
 * EMI request submission endpoints.
 */
class EmiRequestController extends Controller
{
    /**
     * Submit EMI Request
     *
     * @name Submit EMI Request
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:191',
                'email' => 'required|email|max:191',
                'contact_number' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'dob_ad' => 'nullable|date',
                'product_id' => 'required|integer|exists:products,id',
                'monthly_income' => 'required|numeric',
                'finance_amount' => 'required|numeric',
                // Files - ensuring strictly images or PDFs
                'salary_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'citizenship' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'photo' => 'nullable|image|max:2048',
                'bank_statement' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $request->all();

            // Handle File Uploads
            $uploadPath = 'emi/requests';
            if ($request->hasFile('salary_certificate')) {
                $data['salary_certificate'] = $request->file('salary_certificate')->store($uploadPath, 'public');
            }
            if ($request->hasFile('citizenship')) {
                $data['citizenship'] = $request->file('citizenship')->store($uploadPath, 'public');
            }
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store($uploadPath, 'public');
            }
            if ($request->hasFile('bank_statement')) {
                $data['bank_statement'] = $request->file('bank_statement')->store($uploadPath, 'public');
            }

            // Set User ID from Auth
            $data['user_id'] = auth()->id();

            // Default Status
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
