<?php

namespace App\Services;

use App\Models\OrderModel;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentTransactionService
{
    private const REDACT_KEYS = ['secret_key', 'access_key', 'signature'];

    /**
     * Strip secret/signature fields before persisting or logging a gateway payload.
     */
    public function redact(array $payload): array
    {
        return collect($payload)->except(self::REDACT_KEYS)->all();
    }

    /**
     * Amount comparison with a small epsilon for float/decimal rounding.
     */
    public function amountMatches(Transaction $transaction, float $verifiedAmount): bool
    {
        return abs((float) $transaction->amount - $verifiedAmount) < 0.01;
    }

    /**
     * Mark a transaction + its order as paid. Idempotent: a no-op if the
     * transaction is already in a terminal state. Locks the order row for
     * the duration of the state transition to guard against concurrent
     * duplicate callback delivery.
     */
    public function markSuccess(Transaction $transaction, string $gatewayTransactionId, array $rawResponse): Transaction
    {
        return DB::transaction(function () use ($transaction, $gatewayTransactionId, $rawResponse) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->isTerminal()) {
                return $locked;
            }

            $order = OrderModel::whereKey($locked->order_id)->lockForUpdate()->first();

            $locked->update([
                'status' => Transaction::STATUS_SUCCESS,
                'gateway_transaction_id' => $gatewayTransactionId,
                'raw_response' => $this->redact($rawResponse),
            ]);

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $gatewayTransactionId,
                    'status' => OrderModel::STATUS_CONFIRMED,
                ]);
            }

            return $locked;
        });
    }

    /**
     * Mark a transaction as failed/canceled. Idempotent for the same reasons
     * as markSuccess().
     */
    public function markFailed(Transaction $transaction, array $rawResponse, string $status = Transaction::STATUS_FAILED): Transaction
    {
        return DB::transaction(function () use ($transaction, $rawResponse, $status) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->isTerminal()) {
                return $locked;
            }

            $locked->update([
                'status' => $status,
                'raw_response' => $this->redact($rawResponse),
            ]);

            $order = OrderModel::find($locked->order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'failed']);
            }

            return $locked;
        });
    }

    public function logGatewayError(string $gateway, string $message, array $context = []): void
    {
        Log::error("[payment:{$gateway}] {$message}", $this->redact($context));
    }
}
