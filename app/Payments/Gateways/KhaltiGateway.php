<?php

namespace App\Payments\Gateways;

use App\Models\OrderModel;
use App\Models\Transaction;
use App\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Khalti ePayment v2 integration.
 */
class KhaltiGateway implements PaymentGateway
{
    public function __construct(private array $config)
    {
    }

    public function initiate(OrderModel $order, string $transactionUuid): array
    {
        $user = $order->user;

        $response = Http::withHeaders([
            'Authorization' => 'Key '.$this->config['secret_key'],
        ])->timeout(15)->post($this->config['base_url'].'/epayment/initiate/', [
            'return_url' => $this->config['return_url'],
            'website_url' => $this->config['website_url'],
            'amount' => (int) round($order->total * 100),
            'purchase_order_id' => $transactionUuid,
            'purchase_order_name' => "Order #{$order->id}",
            'customer_info' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->contact_number ?? '',
            ],
        ]);

        if (! $response->successful()) {
            return ['payment_url' => null];
        }

        $data = $response->json();

        return ['payment_url' => $data['payment_url'] ?? null];
    }

    public function identifyTransaction(Request $request): ?string
    {
        return $request->query('purchase_order_id');
    }

    public function verify(Transaction $transaction, Request $request): array
    {
        $pidx = $request->query('pidx');

        $lookup = Http::withHeaders([
            'Authorization' => 'Key '.$this->config['secret_key'],
        ])->timeout(15)->post($this->config['base_url'].'/epayment/lookup/', [
            'pidx' => $pidx,
        ]);

        if (! $lookup->successful()) {
            return [
                'verified' => false,
                'raw' => ['error' => 'lookup_failed', 'http_status' => $lookup->status()],
                'error_reason' => 'lookup_failed',
            ];
        }

        $status = $lookup->json();
        $completed = ($status['status'] ?? null) === 'Completed';

        return [
            'verified' => $completed,
            'gateway_transaction_id' => (string) ($status['transaction_id'] ?? $pidx),
            'amount' => (float) ($status['total_amount'] ?? 0) / 100,
            'raw' => $status,
            'error_reason' => $completed ? null : 'not_completed',
        ];
    }
}
