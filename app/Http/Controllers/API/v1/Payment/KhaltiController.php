<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\Payment\KhaltiInitiateRequest;
use App\Models\OrderModel;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * Khalti ePayment v2 integration.
 */
class KhaltiController extends Controller
{
    public function __construct(private PaymentTransactionService $payments)
    {
    }

    /**
     * Initiate Khalti Payment
     *
     * Calls Khalti's initiate API server-to-server and returns the ready-to-use
     * payment_url to redirect the user's browser to.
     *
     * @name Initiate Khalti Payment
     */
    public function initiatePayment(KhaltiInitiateRequest $request)
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
            'gateway' => 'khalti',
            'transaction_uuid' => $transactionUuid,
            'status' => Transaction::STATUS_INITIATED,
            'amount' => $order->total,
        ]);

        $user = $request->user();

        $response = Http::withHeaders([
            'Authorization' => 'Key '.config('payment.khalti.secret_key'),
        ])->timeout(15)->post(config('payment.khalti.base_url').'/epayment/initiate/', [
            'return_url' => config('payment.khalti.return_url'),
            'website_url' => config('payment.khalti.website_url'),
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
            $this->payments->logGatewayError('khalti', 'Initiate call failed', ['http_status' => $response->status()]);

            return response()->json(['message' => 'Failed to initiate Khalti payment'], 502);
        }

        $data = $response->json();

        return response()->json([
            'payment_url' => $data['payment_url'] ?? null,
        ]);
    }

    /**
     * Khalti Callback
     *
     * Called by the browser after Khalti redirects back. Not called by the frontend
     * directly. Never trusts the redirect query params on their own — always
     * re-verifies via Khalti's server-to-server lookup API.
     *
     * @name Khalti Callback
     */
    public function callback(Request $request)
    {
        $purchaseOrderId = $request->query('purchase_order_id');
        $pidx = $request->query('pidx');

        $transaction = $purchaseOrderId
            ? Transaction::where('transaction_uuid', $purchaseOrderId)->where('gateway', 'khalti')->first()
            : null;

        if (! $transaction) {
            $this->payments->logGatewayError('khalti', 'Callback for unknown transaction_uuid', $request->query());

            return redirect(config('payment.frontend_url').'/checkout/Failedpage?reason=khalti_unknown_transaction');
        }

        if ($transaction->isTerminal()) {
            return $this->redirectForStatus($transaction);
        }

        $lookup = Http::withHeaders([
            'Authorization' => 'Key '.config('payment.khalti.secret_key'),
        ])->timeout(15)->post(config('payment.khalti.base_url').'/epayment/lookup/', [
            'pidx' => $pidx,
        ]);

        if (! $lookup->successful()) {
            $this->payments->logGatewayError('khalti', 'Lookup call failed', ['http_status' => $lookup->status()]);
            $this->payments->markFailed($transaction, ['error' => 'lookup_failed']);

            return $this->redirectForStatus($transaction->fresh());
        }

        $status = $lookup->json();

        if (($status['status'] ?? null) !== 'Completed') {
            $this->payments->markFailed($transaction, $status);

            return $this->redirectForStatus($transaction->fresh());
        }

        $verifiedAmount = (float) ($status['total_amount'] ?? 0) / 100;
        if (! $this->payments->amountMatches($transaction, $verifiedAmount)) {
            $this->payments->logGatewayError('khalti', 'Amount mismatch on verification', [
                'expected' => (string) $transaction->amount,
                'verified' => $verifiedAmount,
            ]);
            $this->payments->markFailed($transaction, $status);

            return $this->redirectForStatus($transaction->fresh());
        }

        $this->payments->markSuccess($transaction, (string) ($status['transaction_id'] ?? $pidx), $status);

        return $this->redirectForStatus($transaction->fresh());
    }

    private function redirectForStatus(Transaction $transaction)
    {
        $frontend = config('payment.frontend_url');

        if ($transaction->status === Transaction::STATUS_SUCCESS) {
            return redirect("{$frontend}/checkout/Successpage?orderId={$transaction->order_id}");
        }

        return redirect("{$frontend}/checkout/Failedpage?orderId={$transaction->order_id}&reason=khalti");
    }
}
