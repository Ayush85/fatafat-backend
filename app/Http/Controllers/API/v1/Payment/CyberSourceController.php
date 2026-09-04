<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\API\v1\Payment\Concerns\BuildsCheckoutPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\CyberSourceCompleteRequest;
use App\Http\Requests\API\v1\Payment\CyberSourceInitiateRequest;
use App\Models\Transaction;
use App\Models\UserShippingAddress;
use App\Services\CyberSource\CyberSourceClient;
use App\Services\PaymentTransactionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * CyberSource Unified Checkout integration.
 *
 * Unlike the hosted-form/redirect gateways (eSewa, Khalti, NIC Asia), Unified
 * Checkout is embedded directly in the frontend: the browser mounts
 * CyberSource's JS component with a "capture context" JWT from
 * `initiatePayment`, the customer enters card details in that component, and
 * the component hands back a transient token JWT which the frontend posts to
 * `completePayment` to actually authorize the payment. There's no gateway
 * redirect, so — unlike the other gateways' `callback` — `completePayment` is
 * called by our own authenticated frontend, not by CyberSource.
 */
class CyberSourceController extends Controller
{
    use BuildsCheckoutPayload;

    private const CURRENCY = 'NPR';

    public function __construct(
        private PaymentTransactionService $payments,
        private CyberSourceClient $client,
    ) {
    }

    /**
     * Initiate CyberSource Unified Checkout Payment
     *
     * Validates the cart/shipping/recipient and requests a capture context
     * JWT from CyberSource. No order is created at this point — it's only
     * created once the payment is confirmed in completePayment(), so a
     * failed or abandoned payment never leaves a phantom order behind.
     *
     * @name Initiate CyberSource Payment
     */
    public function initiatePayment(CyberSourceInitiateRequest $request)
    {
        [$payload, $total] = $this->buildCheckoutPayload($request->validated(), $request->user(), 'cybersource');

        $transactionUuid = (string) Str::uuid();

        $transaction = Transaction::create([
            'order_id' => null,
            'user_id' => $request->user()->id,
            'gateway' => 'cybersource',
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $total,
            'currency' => self::CURRENCY,
            'checkout_payload' => $payload,
        ]);

        $user = $request->user();
        $shippingAddress = UserShippingAddress::find($payload['shipping_address_id']);
        $nameParts = preg_split('/\s+/', trim($user->name), 2);

        $captureContextPayload = [
            'clientVersion' => '0.23',
            'targetOrigins' => [config('payment.frontend_url')],
            'allowedCardNetworks' => ['VISA', 'MASTERCARD', 'AMEX', 'JCB', 'DISCOVER', 'DINERSCLUB'],
            'allowedPaymentTypes' => ['PANENTRY'],
            'country' => 'NP',
            'locale' => 'en_US',
            'captureMandate' => [
                'billingType' => 'FULL',
                'requestEmail' => true,
                'requestPhone' => true,
                'showAcceptedNetworkIcons' => true,
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => number_format($total, 2, '.', ''),
                    'currency' => self::CURRENCY,
                ],
                'billTo' => [
                    'firstName' => $nameParts[0] ?? 'Customer',
                    'lastName' => $nameParts[1] ?? $nameParts[0] ?? 'Customer',
                    'email' => $user->email,
                    'phoneNumber' => $user->contact_number ?: ($payload['recipient']['phone'] ?? ''),
                    'address1' => $shippingAddress->landmark ?: $shippingAddress->city,
                    // CyberSource requires a building number; checkout doesn't collect a
                    // street address at all (uncommon in Nepal), so this is a fixed fallback.
                    'buildingNumber' => '1',
                    'locality' => $shippingAddress->city,
                    'administrativeArea' => $shippingAddress->province,
                    'country' => 'NP',
                    'postalCode' => '44600',
                ],
            ],
            'completeMandate' => [
                'type' => 'CAPTURE',
                'decisionManager' => false,
            ],
        ];

        // TEMP DEBUG — remove after testing.
        Log::info('cybersource.capture_context.request', $captureContextPayload);

        $response = $this->client->post('/up/v1/capture-contexts', $captureContextPayload);

        // TEMP DEBUG — remove after testing.
        Log::info('cybersource.capture_context.response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if (! $response->successful()) {
            $this->payments->logGatewayError('cybersource', 'Capture context generation failed', ['http_status' => $response->status()]);
            $this->payments->markFailed($transaction, ['error' => 'capture_context_failed'], Transaction::STATUS_FAILED);

            return response()->json([
                'message' => 'Failed to initiate CyberSource payment',
                'transaction_uuid' => $transactionUuid,
            ], 502);
        }

        return response()->json([
            'capture_context' => trim($response->body()),
            'transaction_uuid' => $transactionUuid,
        ]);
    }

    /**
     * Complete CyberSource Unified Checkout Payment
     *
     * Takes the transient token the Unified Checkout JS component produced
     * and submits it server-to-server to authorize (and capture) the
     * payment. The amount charged is always the one recorded on the
     * transaction at initiate time — never anything supplied by the client —
     * so a tampered request can't pay less than the actual order total.
     *
     * @name Complete CyberSource Payment
     */
    public function completePayment(CyberSourceCompleteRequest $request)
    {
        $transaction = Transaction::where('transaction_uuid', $request->validated('transaction_uuid'))
            ->where('gateway', 'cybersource')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($transaction->isTerminal()) {
            return $this->statusResponse($transaction);
        }

        $response = $this->client->post('/pts/v2/payments', [
            'clientReferenceInformation' => [
                'code' => $transaction->transaction_uuid,
            ],
            'processingInformation' => [
                'capture' => true,
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => number_format((float) $transaction->amount, 2, '.', ''),
                    'currency' => $transaction->currency ?? self::CURRENCY,
                ],
            ],
            'tokenInformation' => [
                'transientTokenJwt' => $request->validated('transient_token'),
            ],
        ]);

        $result = $response->json() ?? [];

        if (! $response->successful() || ($result['status'] ?? null) !== 'AUTHORIZED') {
            $this->payments->logGatewayError('cybersource', 'Payment authorization failed', ['http_status' => $response->status()]);
            $this->payments->markFailed($transaction, $result ?: ['error' => 'payment_failed']);

            return $this->statusResponse($transaction->fresh());
        }

        $this->payments->markSuccess($transaction, (string) ($result['id'] ?? ''), $result);

        return $this->statusResponse($transaction->fresh());
    }

    private function statusResponse(Transaction $transaction)
    {
        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return response()->json([
                'status' => 'success',
                'order_id' => $transaction->order_id,
            ]);
        }

        return response()->json([
            'status' => 'failed',
            'transaction_uuid' => $transaction->transaction_uuid,
        ], 422);
    }
}
