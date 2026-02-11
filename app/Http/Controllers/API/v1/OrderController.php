<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Order;
use App\Models\Cart;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        try {
            $query = Order::where('user_id', auth()->id())
                ->with(['items.product', 'shippingAddress']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $query->orderBy('created_at', 'desc');

            $perPage = $request->input('per_page', 20);
            $orders = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => OrderResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::where('user_id', auth()->id())
                ->with(['items.product', 'shippingAddress'])
                ->find($id);

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            return $this->successResponse(new OrderResource($order));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'shipping_address_id' => 'required|exists:user_shipping_addresses,id',
                'payment_type' => 'required|string',
                'shipping_cost' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $cart = Cart::where('user_id', auth()->id())
                ->where('is_processed', 0)
                ->with('items')
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return $this->errorResponse('Cart is empty', 400);
            }

            DB::beginTransaction();

            $orderTotal = $cart->getCartItemTotal();
            $shippingCost = $request->input('shipping_cost', 0);
            $total = $orderTotal + $shippingCost;

            $order = Order::create([
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
                'shipping_address_id' => $request->shipping_address_id,
                'invoice_number' => 'INV-' . time() . '-' . auth()->id(),
                'status' => Order::STATUS_PLACED,
                'payment_type' => $request->payment_type,
                'shipping_cost' => $shippingCost,
                'order_total' => $orderTotal,
                'total' => $total,
                'discount_coupon' => $cart->discount_coupon,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product_price' => $item->product_price,
                    'product_name' => $item->product_name,
                    'vendor_id' => $item->vendor_id,
                    'product_attributes' => $item->product_attributes,
                ]);
            }

            $cart->markAsDone();

            DB::commit();

            $order->load(['items.product', 'shippingAddress']);

            return $this->successResponse(new OrderResource($order), 'Order placed successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            $order = Order::where('user_id', auth()->id())->find($id);

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            if ($order->status >= Order::STATUS_DISPATCHED) {
                return $this->errorResponse('Cannot cancel order that has been dispatched', 400);
            }

            $order->update([
                'status' => Order::STATUS_CANCELED,
                'cancel_reason' => $request->input('reason', 'Canceled by customer'),
            ]);

            return $this->successResponse(new OrderResource($order), 'Order canceled successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
