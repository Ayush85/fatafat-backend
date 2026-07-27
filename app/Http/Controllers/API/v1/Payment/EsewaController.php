<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\EsewaInitiateRequest;
use App\Models\OrderModel;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * eSewa ePay v2 integration.
 */
class EsewaController extends Controller
{
    public function __construct(private PaymentTransactionService $payments)
    {
    }

    /**
     * Initiate eSewa Payment
     *
     * Generates the signed payload and form URL required to redirect the user to eSewa.
     *
     * @name Initiate eSewa Payment
     */
    public function initiatePayment(EsewaInitiateRequest $request)
    {
        $order = OrderModel::where('user_id', $request->user()->id)->find($request->order_id);

        if (! $order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid'], 409);
        }

        $transactionUuid = (string) Str::uuid();
        $productCode = config('payment.esewa.merchant_code');
        $totalAmount = number_format((float) $order->total, 2, '.', '');

        Transaction::create([
            'order_id' => $order->id,
            'gateway' => 'esewa',
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $order->total,
        ]);

        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $message = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
        $signature = base64_encode(hash_hmac('sha256', $message, config('payment.esewa.secret_key'), true));

        $params = [
            'amount' => $totalAmount,
            'tax_amount' => 0,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'product_service_charge' => 0,
            'product_delivery_charge' => 0,
            'success_url' => config('payment.esewa.success_url'),
            'failure_url' => config('payment.esewa.failure_url'),
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ];

        return response()->json([
            'payment_url' => config('payment.esewa.form_url'),
            'params' => $params,
        ]);
    }

    /**
     * eSewa Callback
     *
     * Called by the browser after eSewa redirects back. Not called by the frontend directly.
     * Never trusts the redirect payload on its own — always re-verifies via eSewa's
     * server-to-server status-check API before marking an order paid.
     *
     * @name eSewa Callback
     */
    public function callback(Request $request)
    {
        $decoded = null;
        if ($request->filled('data')) {
            $decoded = json_decode(base64_decode($request->input('data')), true);
        }

        $transactionUuid = $decoded['transaction_uuid'] ?? null;

        $transaction = $transactionUuid
            ? Transaction::where('transaction_uuid', $transactionUuid)->where('gateway', 'esewa')->first()
            : null;

        if (! $transaction) {
            $this->payments->logGatewayError('esewa', 'Callback received for unknown transaction_uuid', $decoded ?? []);

            return redirect(config('payment.frontend_url').'/checkout/Failedpage?reason=esewa_unknown_transaction');
        }

        if ($transaction->isTerminal()) {
            return $this->redirectForStatus($transaction);
        }

        $statusResponse = Http::timeout(15)->get(config('payment.esewa.status_check_url'), [
            'product_code' => config('payment.esewa.merchant_code'),
            'total_amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'transaction_uuid' => $transactionUuid,
        ]);

        if (! $statusResponse->successful()) {
            $this->payments->logGatewayError('esewa', 'Status-check call failed', ['http_status' => $statusResponse->status()]);
            $this->payments->markFailed($transaction, ['error' => 'status_check_failed']);

            return $this->redirectForStatus($transaction->fresh());
        }

        $status = $statusResponse->json();

        if (($status['status'] ?? null) !== 'COMPLETE') {
            $this->payments->markFailed($transaction, $status);

            return $this->redirectForStatus($transaction->fresh());
        }

        $verifiedAmount = (float) ($status['total_amount'] ?? 0);
        if (! $this->payments->amountMatches($transaction, $verifiedAmount)) {
            $this->payments->logGatewayError('esewa', 'Amount mismatch on verification', [
                'expected' => (string) $transaction->amount,
                'verified' => $verifiedAmount,
            ]);
            $this->payments->markFailed($transaction, $status);

            return $this->redirectForStatus($transaction->fresh());
        }

        $this->payments->markSuccess($transaction, (string) ($status['ref_id'] ?? ''), $status);

        return $this->redirectForStatus($transaction->fresh());
    }

    private function redirectForStatus(Transaction $transaction)
    {
        $frontend = config('payment.frontend_url');

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect("{$frontend}/checkout/Successpage?orderId={$transaction->order_id}");
        }

        return redirect("{$frontend}/checkout/Failedpage?orderId={$transaction->order_id}&reason=esewa");
    }
}
