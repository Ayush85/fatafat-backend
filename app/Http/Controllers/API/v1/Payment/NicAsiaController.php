<?php

namespace App\Http\Controllers\API\v1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @group Payment Gateways
 */
class NicAsiaController extends Controller
{
    /**
     * Initiate NIC Asia Payment
     *
     * Generates the signed payload for CyberSource/NIC Asia payment form.
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

        // Configuration for NIC Asia (You should move these to config/services.php or .env)
        $cybersource_access_key = env('NICASIA_ACCESS_KEY', 'your_access_key');
        $cybersource_profile_id = env('NICASIA_PROFILE_ID', 'your_profile_id');
        $cybersource_secret_key = env('NICASIA_SECRET_KEY', 'your_secret_key');
        $cybersource_transaction_uuid = uniqid(); // Unique ID for transaction
        $cybersource_signed_date_time = gmdate("Y-m-d\TH:i:s\Z");

        $transaction_type = 'sale';
        $currency = 'NPR';
        $amount = $order->total;
        $reference_number = $order->id; // Using Order ID as reference

        // Data to sign
        $signed_field_names = "access_key,profile_id,transaction_uuid,signed_field_names,unsigned_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency";

        $data_to_sign = [
            'access_key' => $cybersource_access_key,
            'profile_id' => $cybersource_profile_id,
            'transaction_uuid' => $cybersource_transaction_uuid,
            'signed_field_names' => $signed_field_names,
            'unsigned_field_names' => '',
            'signed_date_time' => $cybersource_signed_date_time,
            'locale' => 'en',
            'transaction_type' => $transaction_type,
            'reference_number' => $reference_number,
            'amount' => $amount,
            'currency' => $currency,
        ];

        // Generate Signature
        $signature = $this->generateSignature($data_to_sign, $cybersource_secret_key);

        $payload = array_merge($data_to_sign, ['signature' => $signature]);

        return response()->json([
            'payment_url' => env('NICASIA_PAYMENT_URL', 'https://testsecureacceptance.cybersource.com/pay'),
            'params' => $payload
        ]);
    }

    /**
     * Verify NIC Asia Payment
     *
     * Handles the callback from NIC Asia/CyberSource to verify the transaction.
     */
    public function verifyPayment(Request $request)
    {
        // NIC Asia posts back data to this endpoint
        // You should verify the signature here similar to how it was generated, 
        // ensuring the data hasn't been tampered with.

        $status = $request->input('decision'); // ACCEPT, REJECT, ERROR, CANCEL
        $order_id = $request->input('req_reference_number');

        if ($status === 'ACCEPT') {
            $order = Order::find($order_id);
            if ($order) {
                $order->update([
                    'status' => 'processing', // or a dedicated payment_status column
                    'payment_status' => 'paid', // Assuming you add this column
                    'payment_method' => 'nicasia'
                ]);
                return response()->json(['message' => 'Payment successful', 'order_id' => $order_id]);
            }
        }

        return response()->json(['message' => 'Payment failed or declined'], 400);
    }

    private function generateSignature($params, $secretKey)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"]);
        $dataToSign = [];
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . ($params[$field] ?? '');
        }
        $dataToSignStr = implode(",", $dataToSign);

        return base64_encode(hash_hmac('sha256', $dataToSignStr, $secretKey, true));
    }
}
