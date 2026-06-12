<?php

namespace App\Services;

use App\Models\EmiBankModel;
use Exception;

class EmiService
{
    public function calculate(string $bankCode, int $duration, float $financeAmount): array
    {
        $emiBank = EmiBankModel::where('bank_code', $bankCode)->first();

        if (!$emiBank) {
            throw new Exception('Invalid bank code.');
        }

        $key = 'month_' . $duration;
        $percentages = $emiBank->finance_amount_percentage ?? [];

        if (!isset($percentages[$key])) {
            throw new Exception('Invalid EMI configuration for selected duration.');
        }

        // $annualInterest = (float) $percentages[$key];
        // $monthlyRate = ($annualInterest / 12) / 100;
        // if ($monthlyRate == 0) {
        //     $emiPerMonth = round($financeAmount / $duration, 2);
        // } else {
        //     $factor = pow(1 + $monthlyRate, $duration);
        //     $emiPerMonth = round(($financeAmount * $monthlyRate * $factor) / ($factor - 1), 2);
        // }

        $emiPerMonth = round($financeAmount / $duration, 2);

        return [
            'finance_amount' => $financeAmount,
            'duration' => $duration,
            'interest_rate' => 0,
            'emi_per_month' => $emiPerMonth,
        ];
    }
}