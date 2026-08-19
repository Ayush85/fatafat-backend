<?php

namespace App\Http\Controllers\API\v1\Payment\Concerns;

use App\Models\Cart;
use App\Models\User;
use App\Models\UserShippingAddress;
use Illuminate\Validation\ValidationException;

/**
 * Validates the cart/shipping/recipient for an online-gateway checkout and
 * snapshots it into the array shape PaymentTransactionService::markSuccess()
 * expects on Transaction::checkout_payload. Nothing here creates an order —
 * that only happens once the gateway confirms payment.
 */
trait BuildsCheckoutPayload
{
    /**
     * @return array{0: array, 1: float} [checkout payload, total amount]
     */
    protected function buildCheckoutPayload(array $validated, User $user, string $paymentType): array
    {
        $cart = Cart::where([
            'user_id' => $user->id,
            'id' => $validated['cart_id'],
            'is_processed' => 0,
        ])->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart_id' => ['Cart is empty or not found.'],
            ]);
        }

        $orderTotal = $cart->getCartItemTotal();
        $shippingCost = (float) ($validated['shipping_cost'] ?? 0);
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

        if (! empty($validated['shipping_address']['id'])) {
            $shippingAddress = UserShippingAddress::where('id', $validated['shipping_address']['id'])
                ->where('user_id', $user->id)
                ->first();

            if (! $shippingAddress) {
                throw ValidationException::withMessages([
                    'shipping_address.id' => ['Shipping address not found for this user.'],
                ]);
            }

            $shippingAddress->fill($shippingData);
            $shippingAddress->save();
        } else {
            $shippingAddress = UserShippingAddress::create($shippingData);
        }

        $items = $cart->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'product_price' => $item->price,
            'product_name' => $item->product ? $item->product->name : 'Unknown Product',
            'vendor_id' => $item->vendor_id,
            'product_attributes' => $item->product_attributes ?? null,
        ])->all();

        $payload = [
            'cart_id' => $cart->id,
            'items' => $items,
            'shipping_address_id' => $shippingAddress->id,
            'recipient' => [
                'recipient_type' => $validated['recipient']['type'],
                'name' => $validated['recipient']['name'],
                'phone' => $validated['recipient']['phone'],
                'sender_photo' => null,
                'receiver_photo' => null,
                'meta' => null,
            ],
            'payment_type' => $paymentType,
            'discount_coupon' => $cart->discount_coupon,
            'shipping_cost' => $shippingCost,
            'order_total' => $orderTotal,
            'total' => $total,
        ];

        return [$payload, $total];
    }
}
