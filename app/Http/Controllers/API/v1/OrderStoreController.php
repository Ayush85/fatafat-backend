<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\OrderStoreRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderModel;
use App\Models\OrderReceipentModel;
use App\Models\Product;
use App\Models\UserShippingAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @group Orders
 *
 * Order creation, listing, and cancellation endpoints.
 */
class OrderStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Store Order
     *
     * @name Store Order
     */
    public function store(OrderStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        DB::beginTransaction();

        try {

            $cart = Cart::where([
                'user_id' => auth()->id(),
                'id' => $validated['cart_id'],
                'is_processed' => 0,
            ])
                ->with('items.product')
                ->first();

            $orderTotal = $cart->getCartItemTotal();
            $shippingCost = $request->input('shipping_cost', 0);
            $total = $orderTotal + $shippingCost;

        
            $shippingData = [
                'user_id' => $user->id,
                'first_name' => 'shipping_add',
                'last_name' => 'shipping_add',
                'contact_number' => 'shipping_add',
                'label' => $validated['shipping_address']['label'] ?? null,
                'landmark' => $validated['shipping_address']['landmark'] ?? null,
                'city' => $validated['shipping_address']['city'],
                'district' => $validated['shipping_address']['district'],
                'province' => $validated['shipping_address']['province'],
                'country' => $validated['shipping_address']['country'],
                'is_default' => $validated['shipping_address']['is_default'] ?? false,
                'lat' => $validated['shipping_address']['geo']['lat'] ?? null,
                'lng' => $validated['shipping_address']['geo']['lng'] ?? null,
            ];

        
            // Update existing user shipping address when an id is provided; otherwise create
            if (!empty($validated['shipping_address']['id'])) {
                $shippingAddress = UserShippingAddress::where('id', $validated['shipping_address']['id'])
                    ->where('user_id', $user->id)
                    ->first();

                if (!$shippingAddress) {
                    throw ValidationException::withMessages([
                        'shipping_address.id' => ['Shipping address not found for this user.'],
                    ]);
                }

                $shippingAddress->fill($shippingData);
                $shippingAddress->save();
            } else {
                $shippingAddress = UserShippingAddress::create($shippingData);
            }


             $order = OrderModel::create([
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
                'shipping_address_id' => $shippingAddress->id,
                
                'invoice_number' => 'FTS-ORD-'.time().'-'.auth()->id(),
                'status' => OrderModel::STATUS_PLACED,
                'payment_type' => $validated['payment']['payment_type'],
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
                    'product_price' => $item->price,
                    'product_name' => $item->product ? $item->product->name : 'Unknown Product',
                    'vendor_id' => $item->vendor_id,
                    'product_attributes' => $item->product_attributes,
                ]);
            }

            $cart->markAsDone();
            /*
            |--------------------------------------------------------------------------
            | 7. Save recipient photos
            |--------------------------------------------------------------------------
            */
            // Image upload skipped per request; ignore any provided sender/receiver photos for now.
            $senderPhotoPath = null;
            $receiverPhotoPath = null;

            /*
            |--------------------------------------------------------------------------
            | 8. Create order recipient
            |--------------------------------------------------------------------------
            */
            $recipient = OrderReceipentModel::create([
                'order_id' => $order->id,
                'recipient_type' => $validated['recipient']['type'],
                'name' => $validated['recipient']['name'],
                'phone' => $validated['recipient']['phone'],
                'sender_photo' => $senderPhotoPath,
                'receiver_photo' => $receiverPhotoPath,
                'meta' => null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'data' => [
                    'order' => $order,
                    'shipping_address' => $shippingAddress,
                    'recipient' => $recipient,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // private function storeBase64Image(string $base64Image, string $folder): ?string
    // {
    //     if (! preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
    //         return null;
    //     }

    //     $extension = strtolower($matches[1]);
    //     $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    //     if (! in_array($extension, $allowedExtensions, true)) {
    //         return null;
    //     }

    //     $imageData = substr($base64Image, strpos($base64Image, ',') + 1);
    //     $imageData = base64_decode($imageData);

    //     if ($imageData === false) {
    //         return null;
    //     }

    //     $fileName = $folder.'/'.Str::uuid().'.'.$extension;

    //     Storage::disk('public')->put($fileName, $imageData);

    //     return $fileName;
    // }
}
