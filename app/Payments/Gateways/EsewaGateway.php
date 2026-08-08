<?php

namespace App\Payments\Gateways;

use App\Models\OrderModel;
use App\Models\Transaction;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * eSewa ePay v2 integration.
 */
class EsewaGateway implements PaymentGateway
{
    public function __construct(private array $config)
    {
    }

    public function initiate(OrderModel $order, string $transactionUuid): array
    {
        $productCode = $this->config['merchant_code'];
        $totalAmount = number_format((float) $order->total, 2, '.', '');
        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $message = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
        $signature = base64_encode(hash_hmac('sha256', $message, $this->config['secret_key'], true));

        return [
            'payment_url' => $this->config['form_url'],
            'params' => [
                'amount' => $totalAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => $productCode,
                'product_service_charge' => 0,
                'product_delivery_charge' => 0,
                'success_url' => $this->config['success_url'],
                'failure_url' => $this->config['failure_url'],
                'signed_field_names' => $signedFieldNames,
                'signature' => $signature,
            ],
        ];
    }

    public function identifyTransaction(Request $request): ?string
    {
        if (! $request->filled('data')) {
            return null;
        }

        $decoded = json_decode(base64_decode($request->input('data')), true);

        return $decoded['transaction_uuid'] ?? null;
    }

    public function verify(Transaction $transaction, Request $request): array
    {
        $statusResponse = Http::timeout(15)->get($this->config['status_check_url'], [
            'product_code' => $this->config['merchant_code'],
            'total_amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'transaction_uuid' => $transaction->transaction_uuid,
        ]);

        if (! $statusResponse->successful()) {
            return [
                'verified' => false,
                'raw' => ['error' => 'status_check_failed', 'http_status' => $statusResponse->status()],
                'error_reason' => 'status_check_failed',
            ];
        }

        $status = $statusResponse->json();
        $completed = ($status['status'] ?? null) === 'COMPLETE';

        return [
            'verified' => $completed,
            'gateway_transaction_id' => (string) ($status['ref_id'] ?? ''),
            'amount' => (float) ($status['total_amount'] ?? 0),
            'raw' => $status,
            'error_reason' => $completed ? null : 'not_completed',
        ];
    }
}
