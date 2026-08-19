<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\API\v1\Payment\Concerns\BuildsCheckoutPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\NicAsiaInitiateRequest;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * NIC Asia (CyberSource Secure Acceptance) integration.
 */
class NicAsiaController extends Controller
{
    use BuildsCheckoutPayload;

    public function __construct(private PaymentTransactionService $payments)
    {
    }

    /**
     * Initiate NIC Asia Payment
     *
     * Validates the cart/shipping/recipient and generates the signed payload for
     * the CyberSource Secure Acceptance hosted form. No order is created at this
     * point — it's only created once CyberSource confirms payment, so a failed
     * or abandoned payment never leaves a phantom order behind.
     *
     * @name Initiate NIC Asia Payment
     */
    public function initiatePayment(NicAsiaInitiateRequest $request)
    {
        [$payload, $total] = $this->buildCheckoutPayload($request->validated(), $request->user(), 'nic_asia');

        $transactionUuid = (string) Str::uuid();

        Transaction::create([
            'order_id' => null,
            'user_id' => $request->user()->id,
            'gateway' => 'nicasia',
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $total,
            'checkout_payload' => $payload,
        ]);

        $signedFieldNames = 'access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,'
            .'signed_date_time,locale,transaction_type,reference_number,amount,currency';

        $dataToSign = [
            'access_key' => config('payment.nicasia.access_key'),
            'profile_id' => config('payment.nicasia.profile_id'),
            'transaction_uuid' => $transactionUuid,
            'signed_field_names' => $signedFieldNames,
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'locale' => 'en',
            'transaction_type' => 'sale',
            'reference_number' => $transactionUuid,
            'amount' => number_format($total, 2, '.', ''),
            'currency' => 'NPR',
        ];

        $formPayload = array_merge($dataToSign, [
            'signature' => $this->sign($dataToSign, $signedFieldNames, config('payment.nicasia.secret_key')),
        ]);

        return response()->json([
            'payment_url' => config('payment.nicasia.payment_url'),
            'params' => $formPayload,
        ]);
    }

    /**
     * NIC Asia Callback
     *
     * Called by CyberSource/the customer's browser after the transaction, not by the
     * frontend directly. Rejects anything whose signature doesn't verify before
     * touching order state.
     *
     * @name NIC Asia Callback
     */
    public function callback(Request $request)
    {
        $payload = $request->all();
        $signedFieldNames = $payload['signed_field_names'] ?? '';

        if (! $signedFieldNames || ! $this->verifySignature($payload, $signedFieldNames, config('payment.nicasia.secret_key'))) {
            $this->payments->logGatewayError('nicasia', 'Signature verification failed on callback', $payload);

            return redirect(config('payment.frontend_url').'/checkout/Failedpage?reason=nicasia_signature');
        }

        $transactionUuid = $payload['req_reference_number'] ?? null;
        $transaction = $transactionUuid
            ? Transaction::where('transaction_uuid', $transactionUuid)->where('gateway', 'nicasia')->first()
            : null;

        if (! $transaction) {
            $this->payments->logGatewayError('nicasia', 'Callback for unknown transaction_uuid', $payload);

            return redirect(config('payment.frontend_url').'/checkout/Failedpage?reason=nicasia_unknown_transaction');
        }

        if ($transaction->isTerminal()) {
            return $this->redirectForStatus($transaction);
        }

        $verifiedAmount = (float) ($payload['req_amount'] ?? 0);
        $decision = $payload['decision'] ?? null;

        if ($decision !== 'ACCEPT' || ! $this->payments->amountMatches($transaction, $verifiedAmount)) {
            $this->payments->markFailed($transaction, $payload);

            return $this->redirectForStatus($transaction->fresh());
        }

        $this->payments->markSuccess($transaction, (string) ($payload['transaction_id'] ?? ''), $payload);

        return $this->redirectForStatus($transaction->fresh());
    }

    private function sign(array $params, string $signedFieldNames, string $secretKey): string
    {
        $fields = explode(',', $signedFieldNames);
        $parts = [];
        foreach ($fields as $field) {
            $parts[] = $field.'='.($params[$field] ?? '');
        }

        return base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));
    }

    private function verifySignature(array $payload, string $signedFieldNames, string $secretKey): bool
    {
        $expected = $this->sign($payload, $signedFieldNames, $secretKey);
        $actual = $payload['signature'] ?? '';

        return hash_equals($expected, $actual);
    }

    private function redirectForStatus(Transaction $transaction)
    {
        $frontend = config('payment.frontend_url');

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect("{$frontend}/checkout/Successpage?orderId={$transaction->order_id}");
        }

        $ref = $transaction->order_id
            ? "orderId={$transaction->order_id}"
            : "txn={$transaction->transaction_uuid}";

        return redirect("{$frontend}/checkout/Failedpage?{$ref}&reason=nicasia");
    }
}
