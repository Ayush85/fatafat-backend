<?php

namespace App\Http\Controllers\API\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jed\Ecommerce\Cart\Order;
use Jed\Ecommerce\Cart\Cart;
use Jed\Ecommerce\Cart\OrderItem;
use Jed\Ecommerce\Cart\UserShippingAddress;
use Illuminate\Support\Facades\Validator;
use Exception;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => 'required|exists:user_shipping_addresses,id',
            'payment_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        // accurate usage of Cart for API User
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
            'is_processed' => 0
        ]);

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validate Shipping Address belongs to user
        $shippingAddress = UserShippingAddress::where('user_id', $user->id)
            ->where('id', $request->shipping_address_id)
            ->first();

        if (!$shippingAddress) {
            return response()->json(['message' => 'Invalid shipping address'], 400);
        }

        // Create/Update Order logic similar to Website\OrderController
        $order_data = [
            'shipping_address_id' => $request->shipping_address_id,
            'payment_type' => $request->payment_type,
            'status' => Order::STATUS_DRAFT, // Initially draft
            'cart_id' => $cart->id,
            'user_id' => $user->id,
            'order_total' => $cart->getCartItemTotal(),
            'total' => $cart->getCartItemTotal(),
        ];

        $order = Order::where(['cart_id' => $cart->id, 'user_id' => $user->id])->first();
        if (!$order) {
            $order = Order::create($order_data);
        } else {
            $order->update($order_data);
        }

        // For now, assuming direct checkout/cod logic for API v2 
        // similar to processCartOrder in Website controller

        foreach ($cart->items as $cart_item) {
            $order_item = $cart_item->prepareOrderItem(); // Assuming this method exists on CartItem
            $order_item['order_id'] = $order->id;

            $product = $cart_item->product;
            if ($cart_item->product_attributes) {
                $variant = $cart_item->variant(); // Assuming this exists
                if ($variant) {
                    $variant->update([
                        'quantity' => $variant->quantity - $cart_item->quantity
                    ]);
                }
            }

            if ($product) {
                $product->update([
                    'quantity' => $product->quantity - $cart_item->quantity
                ]);
            }

            OrderItem::create($order_item);
        }

        $order->update([
            'invoice_number' => env('ECOMMERCE_ORDER_PREFIX', 'ORD-') . $order->id,
            'status' => Order::STATUS_PLACED
        ]);

        $cart->markAsDone();

        return response()->json(['data' => $order, 'message' => 'Order placed successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->with(['items.product', 'shippingAddress'])
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json(['data' => $order]);
    }
}
