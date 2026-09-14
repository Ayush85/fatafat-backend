<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\OrderModel;
use App\Models\OrderReceipentModel;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentTransactionService
{
    private const REDACT_KEYS = ['secret_key', 'access_key', 'signature'];

    /**
     * Strip secret/signature fields before persisting or logging a gateway payload.
     */
    public function redact(array $payload): array
    {
        return collect($payload)->except(self::REDACT_KEYS)->all();
    }

    /**
     * Amount comparison with a small epsilon for float/decimal rounding.
     */
    public function amountMatches(Transaction $transaction, float $verifiedAmount): bool
    {
        return abs((float) $transaction->amount - $verifiedAmount) < 0.01;
    }

    /**
     * Creates the order (+ items, recipient, cart close-out) from the checkout
     * snapshot captured at initiate time. Only ever called once payment is
     * verified — a failed or abandoned payment never reaches this, so it
     * never leaves a phantom order behind.
     */
    private function materializeOrder(Transaction $transaction, ?string $paymentTypeOverride = null): OrderModel
    {
        $payload = $transaction->checkout_payload;
        $paymentType = $paymentTypeOverride ?? $payload['payment_type'];
        $isPreOrder = $payload['is_pre_order'] ?? false;

        // A pre-order has no pre-existing cart — a real Cart/CartItem pair is
        // created here (rather than leaving cart_id null) purely to satisfy the
        // same order shape every other path in this codebase relies on. Doing
        // this only now, on confirmed payment, means an abandoned pre-order
        // checkout never leaves one behind either.
        if ($isPreOrder) {
            $cart = Cart::create(['user_id' => $transaction->user_id, 'is_processed' => 0]);
            CartItem::create(array_merge($payload['item'], ['cart_id' => $cart->id]));
            $cartId = $cart->id;
            $items = [$payload['item']];
        } else {
            $cartId = $payload['cart_id'];
            $items = $payload['items'];
        }

        $order = OrderModel::create([
            'user_id' => $transaction->user_id,
            'cart_id' => $cartId,
            'shipping_address_id' => $payload['shipping_address_id'],
            'is_pre_order' => $isPreOrder,
            'deposit_amount' => $payload['deposit_amount'] ?? null,
            'invoice_number' => 'FTS-ORD-'.time().'-'.$transaction->user_id,
            'status' => OrderModel::STATUS_PLACED,
            'payment_type' => $paymentType,
            'shipping_cost' => $payload['shipping_cost'],
            'order_total' => $payload['order_total'],
            'total' => $payload['total'],
            'discount_coupon' => $payload['discount_coupon'] ?? null,
        ]);

        // Unlike the synchronous COD checkout, this runs from a payment callback rather
        // than inside the customer's own request — pass their identity through explicitly
        // so the activity log still has a real actor instead of defaulting to null.
        $order->logActivity(
            action: $order->order_status,
            label: $isPreOrder ? 'Pre-order placed' : 'Order placed',
            description: $paymentTypeOverride
                ? 'Order placed via Cash on Delivery after '.$transaction->gateway.' payment failed'
                : ($isPreOrder ? 'Pre-order placed after ' : 'Order placed after ').$transaction->gateway.' payment was confirmed',
            actor: $transaction->user,
        );

        foreach ($items as $item) {
            OrderItem::create(array_merge($item, ['order_id' => $order->id]));
        }

        OrderReceipentModel::create(array_merge($payload['recipient'], ['order_id' => $order->id]));

        if ($cart = Cart::find($cartId)) {
            $cart->markAsDone();
        }

        return $order;
    }

    /**
     * Mark a transaction as paid. Idempotent: a no-op if the transaction is
     * already in a terminal state. If no order exists yet for this
     * transaction (the deferred-order-creation flow for online gateways),
     * the order is created here from the transaction's checkout snapshot —
     * this is the only point at which such an order comes into existence.
     * Locks for the duration of the state transition to guard against
     * concurrent duplicate callback delivery.
     */
    public function markSuccess(Transaction $transaction, string $gatewayTransactionId, array $rawResponse): Transaction
    {
        return DB::transaction(function () use ($transaction, $gatewayTransactionId, $rawResponse) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->isTerminal()) {
                return $locked;
            }

            if (! $locked->order_id) {
                $order = $this->materializeOrder($locked);
                $locked->order_id = $order->id;
            }

            $order = OrderModel::whereKey($locked->order_id)->lockForUpdate()->first();

            $locked->update([
                'order_id' => $locked->order_id,
                'status' => Transaction::STATUS_SUCCESS,
                'gateway_transaction_id' => $gatewayTransactionId,
                'raw_response' => $this->redact($rawResponse),
            ]);

            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $gatewayTransactionId,
                    'status' => OrderModel::STATUS_CONFIRMED,
                ]);
            }

            return $locked;
        });
    }

    /**
     * Mark a transaction as failed/canceled. Idempotent for the same reasons
     * as markSuccess().
     */
    public function markFailed(Transaction $transaction, array $rawResponse, string $status = Transaction::STATUS_FAILED): Transaction
    {
        return DB::transaction(function () use ($transaction, $rawResponse, $status) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->isTerminal()) {
                return $locked;
            }

            $locked->update([
                'status' => $status,
                'raw_response' => $this->redact($rawResponse),
            ]);

            $order = OrderModel::find($locked->order_id);
            if ($order && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'failed']);
            }

            return $locked;
        });
    }

    /**
     * Lets a customer recover a gateway checkout as Cash on Delivery instead —
     * whether the payment attempt is still pending, was declined, or was
     * rejected before ever reaching the gateway. Creates the order from the
     * same checkout snapshot the gateway attempt would have used, then
     * retires the transaction as canceled — it's superseded by the new COD
     * order, not a payment outcome of its own. The only thing this refuses is
     * a transaction that already has an order (a confirmed payment, or a COD
     * switch that already happened) — deliberately not gated on terminal
     * status, since "failed/declined with no order yet" is exactly the state
     * this exists to recover from.
     */
    public function createOrderFromCheckout(Transaction $transaction, string $paymentType): ?OrderModel
    {
        return DB::transaction(function () use ($transaction, $paymentType) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($locked->order_id) {
                return null;
            }

            $order = $this->materializeOrder($locked, $paymentType);

            $locked->update([
                'order_id' => $order->id,
                'status' => Transaction::STATUS_CANCELED,
            ]);

            return $order;
        });
    }

    public function logGatewayError(string $gateway, string $message, array $context = []): void
    {
        Log::error("[payment:{$gateway}] {$message}", $this->redact($context));
    }
}
