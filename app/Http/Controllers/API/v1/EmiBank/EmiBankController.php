<?php

namespace App\Http\Controllers\API\v1\EmiBank;

use App\Http\Controllers\Controller;
use App\Models\EmiBankModel;
use Illuminate\Http\Request;

/**
 * @group Emi Banks
 *
 * Emi Bank listing and detail endpoints.
 */
class EmiBankController extends Controller
{
    /**
     * Emi Bank List
     *
     * @name Emi Bank List
     *
     */
    public function emiBankList(Request $request)
    {
        try {
            $emiBank = EmiBankModel::query()->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'bank_code' => $item->bank_code,
                    'finance_percentage' => $item->finance_amount_percentage
                ];
            });
            return response()->json([
                'success' => true,
                'data' => $emiBank,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

}
