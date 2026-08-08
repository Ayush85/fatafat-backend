<?php

namespace App\Payments\Contracts;

use App\Models\OrderModel;
use App\Models\Transaction;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Build the redirect target for a freshly-created transaction.
     * 'params' present means the frontend must auto-submit a hidden POST form to
     * payment_url; absent/null means a plain browser redirect to payment_url.
     *
     * @return array{payment_url: string, params?: array}
     */
    public function initiate(OrderModel $order, string $transactionUuid): array;

    /**
     * Extract the transaction_uuid a callback claims to belong to, without touching
     * the database. Return null if the payload can't be trusted enough to even
     * attribute it to a transaction (e.g. an invalid gateway signature) — callers
     * must not look up or mutate any transaction in that case.
     */
    public function identifyTransaction(Request $request): ?string;

    /**
     * Verify a callback against the gateway for a transaction that's already been
     * located by identifyTransaction(). Callers still apply their own amount-match
     * check against the returned 'amount' before trusting 'verified'.
     *
     * @return array{verified: bool, gateway_transaction_id?: string, amount?: float, raw: array, error_reason?: string}
     */
    public function verify(Transaction $transaction, Request $request): array;
}
