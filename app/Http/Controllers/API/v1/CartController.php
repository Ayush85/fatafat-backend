<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $cart = Cart::getCart();
            $cart->load('items.product');

            return $this->successResponse(new CartResource($cart));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'product_attributes' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $cart = Cart::getCart();
            $cart->addProduct($request->all());
            $cart->load('items.product');

            return $this->successResponse(new CartResource($cart), 'Product added to cart');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function updateItem(Request $request, $itemId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $cart = Cart::getCart();
            $cartItem = CartItem::where('id', $itemId)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$cartItem) {
                return $this->errorResponse('Cart item not found', 404);
            }

            $cartItem->update(['quantity' => $request->quantity]);
            $cart->load('items.product');

            return $this->successResponse(new CartResource($cart), 'Cart updated');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function removeItem($itemId)
    {
        try {
            $cart = Cart::getCart();
            $cartItem = CartItem::where('id', $itemId)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$cartItem) {
                return $this->errorResponse('Cart item not found', 404);
            }

            $cartItem->delete();
            $cart->load('items.product');

            return $this->successResponse(new CartResource($cart), 'Item removed from cart');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function clear()
    {
        try {
            $cart = Cart::getCart();
            $cart->items()->delete();

            return $this->successResponse(null, 'Cart cleared');

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
