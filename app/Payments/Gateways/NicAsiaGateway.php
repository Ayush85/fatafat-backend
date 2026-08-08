<?php

namespace App\Payments\Gateways;

use App\Models\OrderModel;
use App\Models\Transaction;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;

/**
 * NIC Asia (CyberSource Secure Acceptance) integration.
 */
class NicAsiaGateway implements PaymentGateway
{
    public function __construct(private array $config)
    {
    }

    public function initiate(OrderModel $order, string $transactionUuid): array
    {
        $signedFieldNames = 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,'
            .'signed_date_time,locale,transaction_type,reference_number,amount,currency';

        $dataToSign = [
            'access_key' => $this->config['access_key'],
            'profile_id' => $this->config['profile_id'],
            'transaction_uuid' => $transactionUuid,
            'signed_field_names' => $signedFieldNames,
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'locale' => 'en',
            'transaction_type' => 'sale',
            'reference_number' => $transactionUuid,
            'amount' => number_format((float) $order->total, 2, '.', ''),
            'currency' => 'NPR',
        ];

        $payload = array_merge($dataToSign, [
            'signature' => $this->sign($dataToSign, $signedFieldNames),
        ]);

        return [
            'payment_url' => $this->config['payment_url'],
            'params' => $payload,
        ];
    }

    public function identifyTransaction(Request $request): ?string
    {
        $payload = $request->all();
        $signedFieldNames = $payload['signed_field_names'] ?? '';

        if (! $signedFieldNames || ! $this->verifySignature($payload, $signedFieldNames)) {
            return null;
        }

        return $payload['req_reference_number'] ?? null;
    }

    public function verify(Transaction $transaction, Request $request): array
    {
        $payload = $request->all();
        $decision = $payload['decision'] ?? null;
        $accepted = $decision === 'ACCEPT';

        return [
            'verified' => $accepted,
            'gateway_transaction_id' => (string) ($payload['transaction_id'] ?? ''),
            'amount' => (float) ($payload['req_amount'] ?? 0),
            'raw' => $payload,
            'error_reason' => $accepted ? null : 'declined',
        ];
    }

    private function sign(array $params, string $signedFieldNames): string
    {
        $fields = explode(',', $signedFieldNames);
        $parts = [];
        foreach ($fields as $field) {
            $parts[] = $field.'='.($params[$field] ?? '');
        }

        return base64_encode(hash_hmac('sha256', implode(',', $parts), $this->config['secret_key'], true));
    }

    private function verifySignature(array $payload, string $signedFieldNames): bool
    {
        $expected = $this->sign($payload, $signedFieldNames);
        $actual = $payload['signature'] ?? '';

        return hash_equals($expected, $actual);
    }
}
