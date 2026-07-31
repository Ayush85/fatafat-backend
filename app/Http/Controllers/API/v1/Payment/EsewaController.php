<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Models\OrderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group Payment Gateways
 *
 * eSewa ePay v2 integration.
 * @see https://developer.esewa.com.np/pages/Intent#transactionflow
 */
class EsewaController extends Controller
{
    /**
     * Initiate eSewa Payment
     *
     * Generates the signed form payload required to redirect the user to eSewa.
     *
     * @name Initiate eSewa Payment
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = OrderModel::where('user_id', auth()->id())->find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        if ($order->status >= OrderModel::STATUS_CONFIRMED) {
            return response()->json(['message' => 'Order has already been paid for'], 422);
        }

        $productCode = config('services.esewa.product_code');
        $secretKey = config('services.esewa.secret_key');

        // Total amount charged is the server-side order total, never a client-supplied value.
        $totalAmount = number_format((float) $order->total, 2, '.', '');
        $transactionUuid = $order->id.'-'.now()->format('YmdHis').'-'.Str::random(4);

        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $signatureString = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
        $signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));

        $order->payment_reference = $transactionUuid;
        $order->save();

        $params = [
            'amount' => $totalAmount,
            'tax_amount' => '0',
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $productCode,
            'product_service_charge' => '0',
            'product_delivery_charge' => '0',
            'success_url' => route('esewa.success'),
            'failure_url' => route('esewa.failure'),
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ];

        return response()->json([
            'payment_url' => config('services.esewa.form_url'),
            'params' => $params,
        ]);
    }

    /**
     * eSewa redirects the user's browser here (GET) after a completed payment.
     * Not part of the authenticated API — eSewa cannot send auth headers.
     */
    public function success(Request $request)
    {
        $order = null;

        try {
            $encoded = $request->query('data');
            if (!$encoded) {
                return $this->redirectToFailure();
            }

            $decoded = json_decode(base64_decode($encoded), true);
            if (!is_array($decoded)) {
                return $this->redirectToFailure();
            }

            foreach (['transaction_code', 'status', 'total_amount', 'transaction_uuid', 'product_code', 'signed_field_names', 'signature'] as $key) {
                if (!array_key_exists($key, $decoded)) {
                    return $this->redirectToFailure();
                }
            }

            $order = OrderModel::where('payment_reference', $decoded['transaction_uuid'])->first();

            if (!$this->signatureIsValid($decoded)) {
                Log::warning('eSewa success callback signature mismatch', ['transaction_uuid' => $decoded['transaction_uuid']]);

                return $this->redirectToFailure($order ? $order->id : null);
            }

            if (!$order || $decoded['status'] !== 'COMPLETE') {
                return $this->redirectToFailure($order ? $order->id : null);
            }

            $expectedTotal = number_format((float) $order->total, 2, '.', '');
            if (number_format((float) $decoded['total_amount'], 2, '.', '') !== $expectedTotal) {
                Log::warning('eSewa success callback amount mismatch', ['order_id' => $order->id]);

                return $this->redirectToFailure($order->id);
            }

            if (!$this->confirmWithStatusCheck($decoded)) {
                return $this->redirectToFailure($order->id);
            }

            if ($order->status < OrderModel::STATUS_CONFIRMED) {
                $order->status = OrderModel::STATUS_CONFIRMED;
                $order->save();
            }

            return $this->redirectToSuccess($order->id);
        } catch (\Throwable $e) {
            Log::error('eSewa success callback failed', ['error' => $e->getMessage()]);

            return $this->redirectToFailure($order ? $order->id : null);
        }
    }

    /**
     * eSewa redirects the user's browser here (GET) after a canceled/failed payment.
     */
    public function failure(Request $request)
    {
        $orderId = null;

        $encoded = $request->query('data');
        if ($encoded) {
            $decoded = json_decode(base64_decode($encoded), true);
            if (is_array($decoded) && !empty($decoded['transaction_uuid'])) {
                $order = OrderModel::where('payment_reference', $decoded['transaction_uuid'])->first();
                $orderId = $order ? $order->id : null;
            }
        }

        return $this->redirectToFailure($orderId);
    }

    private function signatureIsValid(array $decoded): bool
    {
        $secretKey = config('services.esewa.secret_key');
        $signedFieldNames = explode(',', $decoded['signed_field_names']);

        $parts = [];
        foreach ($signedFieldNames as $field) {
            $field = trim($field);
            $parts[] = $field.'='.($decoded[$field] ?? '');
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));

        return hash_equals($expectedSignature, (string) $decoded['signature']);
    }

    private function confirmWithStatusCheck(array $decoded): bool
    {
        $response = Http::get(config('services.esewa.status_check_url'), [
            'product_code' => $decoded['product_code'],
            'total_amount' => $decoded['total_amount'],
            'transaction_uuid' => $decoded['transaction_uuid'],
        ]);

        return $response->successful() && $response->json('status') === 'COMPLETE';
    }

    private function redirectToSuccess(int $orderId)
    {
        $frontendUrl = rtrim(config('services.esewa.frontend_url'), '/');

        return redirect()->away("{$frontendUrl}/checkout/Successpage?orderId={$orderId}");
    }

    private function redirectToFailure(?int $orderId = null)
    {
        $frontendUrl = rtrim(config('services.esewa.frontend_url'), '/');
        $query = $orderId ? "?orderId={$orderId}&reason=esewa" : '?reason=esewa';

        return redirect()->away("{$frontendUrl}/checkout/Failedpage{$query}");
    }
}
