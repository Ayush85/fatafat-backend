<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\PaymentInitiateRequest;
use App\Models\OrderModel;
use App\Models\Transaction;
use App\Payments\PaymentGatewayFactory;
use App\Services\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * Generic entry point for every payment gateway. Gateway-specific behavior lives in
 * each driver under App\Payments\Gateways; this controller only handles what's
 * common to all of them (order/transaction bookkeeping and success/fail routing).
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentGatewayFactory $gateways,
        private PaymentTransactionService $payments
    ) {
    }

    /**
     * Initiate Payment
     *
     * Creates a transaction and asks the {gateway} driver for a redirect target.
     *
     * @name Initiate Payment
     */
    public function initiate(PaymentInitiateRequest $request, string $gateway)
    {
        $order = OrderModel::where('user_id', $request->user()->id)->find($request->order_id);

        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid'], 409);
        }

        $transactionUuid = (string) Str::uuid();

        Transaction::create([
            'order_id' => $order->id,
            'gateway' => $gateway,
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $order->total,
        ]);

        $result = $this->gateways->make($gateway)->initiate($order, $transactionUuid);

        if (empty($result['payment_url'])) {
            $this->payments->logGatewayError($gateway, 'Initiate call failed');

            return response()->json(['message' => 'Failed to initiate payment'], 502);
        }

        return response()->json($result);
    }

    /**
     * Payment Callback
     *
     * Called by the gateway (server-to-server or via the customer's browser being
     * redirected/auto-submitted back), never by the frontend directly. Never trusts
     * the callback payload on its own — always re-verifies via the driver before
     * touching order state.
     *
     * @name Payment Callback
     */
    public function callback(Request $request, string $gateway)
    {
        $driver = $this->gateways->make($gateway);
        $transactionUuid = $driver->identifyTransaction($request);

        if (! $transactionUuid) {
            $this->payments->logGatewayError($gateway, 'Callback could not be attributed to a transaction', $request->all());

            return redirect(config('payment.frontend_url')."/checkout/Failedpage?reason={$gateway}");
        }

        $transaction = Transaction::where('transaction_uuid', $transactionUuid)->where('gateway', $gateway)->first();

        if (! $transaction) {
            $this->payments->logGatewayError($gateway, 'Callback for unknown transaction_uuid', $request->all());

            return redirect(config('payment.frontend_url')."/checkout/Failedpage?reason={$gateway}");
        }

        if ($transaction->isTerminal()) {
            return $this->redirectForStatus($transaction);
        }

        $result = $driver->verify($transaction, $request);

        if (! ($result['verified'] ?? false)) {
            $this->payments->logGatewayError($gateway, $result['error_reason'] ?? 'verification_failed', $result['raw'] ?? []);
            $this->payments->markFailed($transaction, $result['raw'] ?? []);

            return $this->redirectForStatus($transaction->fresh());
        }

        if (! $this->payments->amountMatches($transaction, (float) ($result['amount'] ?? 0))) {
            $this->payments->logGatewayError($gateway, 'Amount mismatch on verification', [
                'expected' => (string) $transaction->amount,
                'verified' => $result['amount'] ?? null,
            ]);
            $this->payments->markFailed($transaction, $result['raw'] ?? []);

            return $this->redirectForStatus($transaction->fresh());
        }

        $this->payments->markSuccess($transaction, (string) ($result['gateway_transaction_id'] ?? ''), $result['raw'] ?? []);

        return $this->redirectForStatus($transaction->fresh());
    }

    private function redirectForStatus(Transaction $transaction)
    {
        $frontend = config('payment.frontend_url');

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect("{$frontend}/checkout/Successpage?orderId={$transaction->order_id}");
        }

        return redirect("{$frontend}/checkout/Failedpage?orderId={$transaction->order_id}&reason={$transaction->gateway}");
    }
}
