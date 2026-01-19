<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\EmiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EmiRequestController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:191',
                'email' => 'required|email|max:191',
                'contact_number' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'dob_ad' => 'nullable|date',
                'product_id' => 'required|integer',
                'monthly_income' => 'required|numeric',
                'salary_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'citizenship' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'photo' => 'nullable|image|max:2048',
                'bank_statement' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'finance_amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle File Uploads
            if ($request->hasFile('salary_certificate')) {
                $data['salary_certificate'] = $request->file('salary_certificate')->store('emi/salary', 'public');
            }
            if ($request->hasFile('citizenship')) {
                $data['citizenship'] = $request->file('citizenship')->store('emi/citizenship', 'public');
            }
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('emi/photos', 'public');
            }
            if ($request->hasFile('bank_statement')) {
                $data['bank_statement'] = $request->file('bank_statement')->store('emi/docs', 'public');
            }

            // Defaults
            $data['user_id'] = auth('sanctum')->id() ?? null;
            $data['status'] = 0; // Pending

            $emiRequest = EmiRequest::create($data);

            return response()->json([
                'success' => true,
                'data' => $emiRequest,
                'message' => 'EMI Request submitted successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
