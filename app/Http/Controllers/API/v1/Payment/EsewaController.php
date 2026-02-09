<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * @group Payment Gateways
 *
 * APIs for handling payment gateway integrations.
 */
class EsewaController extends Controller
{
    /**
     * Initiate eSewa Payment
     *
     * Generates the payment payload and URL required to redirect the user to eSewa.
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::where('user_id', auth()->id())->find($request->order_id);

        if (!$order) {
            return response()->json(['message' => 'Order not found or unauthorized'], 404);
        }

        // eSewa Config
        $merchant_code = env('ESEWA_MERCHANT_CODE', 'EPAYTEST'); // Use 'EPAYTEST' for testing
        $payment_url = env('ESEWA_PAYMENT_URL', 'https://uat.esewa.com.np/epay/main');

        $params = [
            'amt' => $order->total,
            'pdc' => 0, // Delivery Charge
            'psc' => 0, // Service Charge
            'txAmt' => 0, // Tax Amount
            'tAmt' => $order->total, // Total Amount
            'pid' => $order->id, // Product/Order ID
            'scd' => $merchant_code,
            'su' => route('esewa.success'), // Success URL
            'fu' => route('esewa.failure'), // Failure URL
        ];

        return response()->json([
            'payment_url' => $payment_url,
            'params' => $params
        ]);
    }

    /**
     * Verify eSewa Payment
     *
     * Verifies the payment status with eSewa after the user is redirected back.
     */
    public function verifyPayment(Request $request)
    {
        // eSewa redirects to Success URL with ?oid={pid}&amt={amt}&refId={refId}

        $order_id = $request->input('oid');
        $amount = $request->input('amt');
        $refId = $request->input('refId');

        // Verify with eSewa API (Server-to-Server verification is recommended)
        $url = env('ESEWA_VERIFICATION_URL', 'https://uat.esewa.com.np/epay/transrec');

        $data = [
            'amt' => $amount,
            'rid' => $refId,
            'pid' => $order_id,
            'scd' => env('ESEWA_MERCHANT_CODE', 'EPAYTEST'),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if (strpos($response, "Success") !== false) {
            $order = Order::find($order_id);
            if ($order) {
                $order->update([
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'payment_method' => 'esewa',
                    'transaction_id' => $refId
                ]);
                return response()->json(['message' => 'Payment successful', 'transaction_id' => $refId]);
            }
        }

        return response()->json(['message' => 'Payment verification failed'], 400);
    }
}
