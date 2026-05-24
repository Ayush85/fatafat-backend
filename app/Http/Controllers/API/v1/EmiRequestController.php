<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\EmiRequest;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmiRequestController extends Controller
{
    private static array $statusMap = [
        EmiRequest::STATUS_PENDING    => 'pending',
        EmiRequest::STATUS_PROCESSING => 'processing',
        EmiRequest::STATUS_APPROVED   => 'approved',
        EmiRequest::STATUS_FINISHED   => 'completed',
        EmiRequest::STATUS_CANCELLED  => 'cancelled',
    ];

    private static array $typeMap = [
        'credit_card' => 'craditcard',
        'citizenship' => 'with_cittizen',
        'apply_card'  => 'with_new_card_Apply',
    ];

    private function formatOrder(EmiRequest $req): array
    {
        $variant = null;
        if ($req->product_variant) {
            $attrs = is_array($req->product_variant) ? $req->product_variant : json_decode($req->product_variant, true);
            $variant = $attrs['Color'] ?? $attrs['color'] ?? null;
        }

        return [
            'id'                 => $req->id,
            'applicationtype'    => self::$typeMap[$req->emi_type] ?? $req->emi_type,
            'status'             => self::$statusMap[$req->status] ?? 'pending',
            'created_at'         => $req->created_at,
            'paid_installments'  => 0,
            'total_installments' => $req->emi_mode,
            'document_note'      => null,
            'product'            => [
                'name'    => $req->product?->name,
                'price'   => $req->product_price,
                'varient' => $variant,
            ],
            'formdata' => [
                'personalInfo'    => [
                    'name'  => $req->name,
                    'phone' => $req->contact_number,
                ],
                'emiCalculation'  => [
                    'duration'       => $req->emi_mode,
                    'downPayment'    => $req->down_payment,
                    'financeAmount'  => $req->finance_amount,
                    'paymentpermonth' => $req->emi_per_month,
                ],
                'bankInfo'        => [],
                'granterInfo'     => [],
                'creditCard'      => [],
            ],
        ];
    }

    public function index(Request $request)
    {
        try {
            $requests = EmiRequest::where('user_id', auth()->id())
                ->with('product')
                ->latest()
                ->get()
                ->map(fn ($req) => $this->formatOrder($req));

            return response()->json([
                'success' => true,
                'data'    => $requests,
            ]);
        } catch (\Throwable $th) {
            Log::error('EmiRequest index error: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $emiRequest = EmiRequest::where('user_id', auth()->id())
                ->with('product')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $this->formatOrder($emiRequest),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 404);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $emiRequest = EmiRequest::where('user_id', auth()->id())->findOrFail($id);

            if ($request->hasFile('document')) {
                $fileUploadService = app(FileUploadService::class);
                $fileUploadService->uploadWithUsage(
                    file: $request->file('document'),
                    folder: 'emi-requests/' . $emiRequest->id . '/documents',
                    usageType: $emiRequest->getTable(),
                    usageId: $emiRequest->id,
                    title: 'additional_document',
                    altText: 'additional_document',
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
            ]);
        } catch (\Throwable $th) {
            Log::error('EmiRequest update error: ' . $th->getMessage());
            return response()->json(['success' => false, 'message' => $th->getMessage()], 422);
        }
    }

    public function destroy(int $id)
    {
        try {
            $emiRequest = EmiRequest::where('user_id', auth()->id())
                ->where('status', EmiRequest::STATUS_PENDING)
                ->findOrFail($id);

            $emiRequest->delete();

            return response()->json(['success' => true, 'message' => 'EMI request cancelled.']);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 422);
        }
    }
}
