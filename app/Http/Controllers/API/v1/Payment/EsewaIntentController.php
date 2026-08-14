<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\EsewaIntentInitiateRequest;
use App\Models\OrderModel;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * eSewa Intent Payment integration (app deeplink flow, for mobile browsers).
 */
class EsewaIntentController extends Controller
{
    /**
     * Fields that must be part of signed_field_names on a callback — without
     * this, a caller could pick a subset of fields to sign and leave status/
     * amount/correlation_id (the fields we actually act on) unauthenticated.
     */
    private const REQUIRED_CALLBACK_SIGNED_FIELDS = ['correlation_id', 'status', 'amount'];

    public function __construct(private PaymentTransactionService $payments)
    {
    }

    /**
     * Initiate eSewa Intent Payment
     *
     * Books a payment with eSewa and returns a deeplink to redirect the user's
     * mobile browser to the eSewa app.
     *
     * @name Initiate eSewa Intent Payment
     */
    public function initiatePayment(EsewaIntentInitiateRequest $request)
    {
        $order = OrderModel::where('user_id', $request->user()->id)->find($request->order_id);

        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid'], 409);
        }

        $transactionUuid = (string) Str::uuid();
        $productCode = config('payment.esewa_intent.product_code');
        $amount = $this->wireAmount((float) $order->total);

        Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'esewa_intent',
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $order->total,
        ]);

        $signedFieldNames = 'product_code,amount,transaction_uuid';
        $signature = $this->sign([
            'product_code' => $productCode,
            'amount' => $amount,
            'transaction_uuid' => $transactionUuid,
        ], $signedFieldNames);

        $response = Http::timeout(15)->post(config('payment.esewa_intent.book_url'), [
            'product_code' => $productCode,
            'amount' => $amount,
            'transaction_uuid' => $transactionUuid,
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
            'callback_url' => config('payment.esewa_intent.callback_url'),
            'redirect_url' => config('payment.esewa_intent.redirect_url').'?txn='.$transactionUuid,
            'properties' => [
                'customer_id' => (string) $order->user_id,
                'remarks' => "Order #{$order->id}",
            ],
        ]);

        if (! $response->successful()) {
            $this->payments->logGatewayError('esewa_intent', 'Book call failed', [
                'http_status' => $response->status(),
                'body' => $response->json(),
            ]);

            return response()->json(['message' => 'Failed to initiate eSewa payment'], 502);
        }

        $data = $response->json('data', []);
        $bookingId = $data['booking_id'] ?? null;
        $deeplink = $data['deeplink'] ?? null;

        if (! $bookingId || ! $deeplink) {
            $this->payments->logGatewayError('esewa_intent', 'Book response missing booking_id/deeplink', $response->json() ?? []);

            return response()->json(['message' => 'Failed to initiate eSewa payment'], 502);
        }

        Transaction::where('transaction_uuid', $transactionUuid)->update([
            'booking_id' => $bookingId,
            'correlation_id' => $data['correlation_id'] ?? null,
        ]);

        return response()->json([
            'payment_url' => $deeplink,
        ]);
    }

    /**
     * eSewa Intent Manual Status Check
     *
     * Lets the frontend force a status re-check for an order's eSewa Intent
     * transaction, per eSewa's guidance to poll this when no callback/redirect
     * has arrived within five minutes.
     *
     * @name eSewa Intent Status
     */
    public function status(Request $request)
    {
        $request->validate(['order_id' => 'required|integer|exists:orders,id']);

        $order = OrderModel::where('user_id', $request->user()->id)->find($request->order_id);
        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        $transaction = Transaction::where('order_id', $order->id)->where('gateway', 'esewa_intent')->latest()->first();
        if (! $transaction) {
            return response()->json(['message' => 'No eSewa Intent transaction found for this order'], 404);
        }

        $transaction = $this->refreshStatus($transaction);

        return response()->json(['status' => $transaction->status]);
    }

    /**
     * Cancel eSewa Intent Payment
     *
     * Cancels a booked-but-not-completed eSewa Intent transaction.
     *
     * @name Cancel eSewa Intent Payment
     */
    public function cancel(Request $request)
    {
        $request->validate(['order_id' => 'required|integer|exists:orders,id']);

        $order = OrderModel::where('user_id', $request->user()->id)->find($request->order_id);
        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        $transaction = Transaction::where('order_id', $order->id)->where('gateway', 'esewa_intent')->latest()->first();
        if (! $transaction) {
            return response()->json(['message' => 'No eSewa Intent transaction found for this order'], 404);
        }

        if ($transaction->isTerminal()) {
            return response()->json(['status' => $transaction->status]);
        }

        if (! $transaction->booking_id) {
            $this->payments->markFailed($transaction, ['error' => 'no_booking_id'], Transaction::STATUS_CANCELED);

            return response()->json(['status' => 'canceled']);
        }

        $productCode = config('payment.esewa_intent.product_code');
        $signedFieldNames = 'booking_id,product_code';
        $signature = $this->sign([
            'booking_id' => $transaction->booking_id,
            'product_code' => $productCode,
        ], $signedFieldNames);

        $response = Http::timeout(15)->post(config('payment.esewa_intent.cancel_url'), [
            'booking_id' => $transaction->booking_id,
            'product_code' => $productCode,
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ]);

        if (! $response->successful()) {
            $this->payments->logGatewayError('esewa_intent', 'Cancel call failed', ['http_status' => $response->status()]);

            return response()->json(['message' => 'Failed to cancel eSewa payment'], 502);
        }

        $this->payments->markFailed($transaction, $response->json() ?? [], Transaction::STATUS_CANCELED);

        return response()->json(['status' => 'canceled']);
    }

    /**
     * eSewa Intent Redirect Return
     *
     * The browser is returned here after the user completes/cancels payment in
     * the eSewa app. Never trusts the redirect on its own — always re-verifies
     * via eSewa's status-check API before marking an order paid.
     *
     * @name eSewa Intent Redirect Return
     */
    public function redirectReturn(Request $request)
    {
        $transactionUuid = $request->query('txn');

        $transaction = $transactionUuid
            ? Transaction::where('transaction_uuid', $transactionUuid)->where('gateway', 'esewa_intent')->first()
            : null;

        if (! $transaction) {
            $this->payments->logGatewayError('esewa_intent', 'Redirect return for unknown transaction', $request->query());

            return redirect(config('payment.frontend_url').'/checkout/Failedpage?reason=esewa_intent_unknown_transaction');
        }

        if (! $transaction->isTerminal()) {
            $this->refreshStatus($transaction);
        }

        return $this->redirectForStatus($transaction->fresh());
    }

    /**
     * eSewa Intent Callback
     *
     * Server-to-server notification eSewa sends once a transaction reaches a
     * final status. Not called by the frontend. Signature-verified before any
     * order state changes.
     *
     * @name eSewa Intent Callback
     */
    public function callback(Request $request)
    {
        $payload = $request->all();
        $signedFieldNames = $payload['signed_field_names'] ?? '';
        $signedFields = $signedFieldNames ? array_map('trim', explode(',', $signedFieldNames)) : [];
        $missingRequiredFields = array_diff(self::REQUIRED_CALLBACK_SIGNED_FIELDS, $signedFields);

        if (! $signedFieldNames || $missingRequiredFields || ! $this->verifySignature($payload, $signedFieldNames)) {
            $this->payments->logGatewayError('esewa_intent', 'Signature verification failed on callback', $payload);

            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $correlationId = $payload['correlation_id'] ?? null;
        $transaction = $correlationId
            ? Transaction::where('correlation_id', $correlationId)->where('gateway', 'esewa_intent')->first()
            : null;

        if (! $transaction) {
            $this->payments->logGatewayError('esewa_intent', 'Callback for unknown correlation_id', $payload);

            return response()->json(['message' => 'Unknown transaction'], 404);
        }

        if ($transaction->isTerminal()) {
            return response()->json(['message' => 'Already processed']);
        }

        $status = $payload['status'] ?? null;
        $verifiedAmount = (float) ($payload['amount'] ?? 0);

        if ($status !== 'SUCCESS' || ! $this->payments->amountMatches($transaction, $verifiedAmount)) {
            $this->payments->markFailed($transaction, $payload, $status === 'CANCELED' ? Transaction::STATUS_CANCELED : Transaction::STATUS_FAILED);

            return response()->json(['message' => 'Recorded']);
        }

        $this->payments->markSuccess($transaction, (string) ($payload['reference_code'] ?? ''), $payload);

        return response()->json(['message' => 'Recorded']);
    }

    /**
     * Calls eSewa's status-check API and updates the transaction/order accordingly.
     * A no-op for transactions already in a terminal state.
     */
    private function refreshStatus(Transaction $transaction): Transaction
    {
        if ($transaction->isTerminal()) {
            return $transaction;
        }

        $productCode = config('payment.esewa_intent.product_code');
        $signedFieldNames = 'booking_id,product_code,correlation_id';
        $signature = $this->sign([
            'booking_id' => $transaction->booking_id,
            'product_code' => $productCode,
            'correlation_id' => $transaction->correlation_id,
        ], $signedFieldNames);

        $response = Http::timeout(15)->post(config('payment.esewa_intent.status_url'), [
            'booking_id' => $transaction->booking_id,
            'product_code' => $productCode,
            'correlation_id' => $transaction->correlation_id,
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ]);

        if (! $response->successful()) {
            $this->payments->logGatewayError('esewa_intent', 'Status-check call failed', ['http_status' => $response->status()]);

            return $transaction->fresh();
        }

        $data = $response->json('data', []);
        $status = $data['status'] ?? null;

        if ($status === 'SUCCESS') {
            $this->payments->markSuccess($transaction, (string) ($data['transaction_id'] ?? $data['reference_code'] ?? ''), $data);
        } elseif (in_array($status, ['FAILED', 'CANCELED', 'REVERTED'], true)) {
            $this->payments->markFailed($transaction, $data, $status === 'CANCELED' ? Transaction::STATUS_CANCELED : Transaction::STATUS_FAILED);
        }

        return $transaction->fresh();
    }

    /**
     * eSewa's book/status/cancel/callback payloads carry amounts as bare JSON
     * numbers (integer when whole), and the signature must be computed over
     * the exact same representation that goes on the wire.
     */
    private function wireAmount(float $amount): int|float
    {
        return $amount == floor($amount) ? (int) $amount : $amount;
    }

    private function sign(array $params, string $signedFieldNames): string
    {
        $fields = explode(',', $signedFieldNames);
        $parts = [];
        foreach ($fields as $field) {
            $parts[] = $field.'='.($params[$field] ?? '');
        }

        return base64_encode(hash_hmac('sha256', implode(',', $parts), config('payment.esewa_intent.access_key'), true));
    }

    private function verifySignature(array $payload, string $signedFieldNames): bool
    {
        $expected = $this->sign($payload, $signedFieldNames);
        $actual = $payload['signature'] ?? '';

        return hash_equals($expected, $actual);
    }

    private function redirectForStatus(Transaction $transaction)
    {
        $frontend = config('payment.frontend_url');

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect("{$frontend}/checkout/Successpage?orderId={$transaction->order_id}");
        }

        return redirect("{$frontend}/checkout/Failedpage?orderId={$transaction->order_id}&reason=esewa_intent");
    }
}
