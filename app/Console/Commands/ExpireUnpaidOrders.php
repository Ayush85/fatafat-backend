<?php

namespace App\Console\Commands;

use App\Models\OrderModel;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--minutes=30 : How old an unresolved gateway checkout/order must be before it is canceled}';

    protected $description = 'Cancel stale online-gateway checkouts that never completed payment: pending transactions that never became an order (the normal case), plus a safety net for any legacy/unpaid Placed orders.';

    private const GATEWAY_PAYMENT_TYPES = ['esewa', 'esewa_intent', 'khalti', 'nic_asia'];

    public function handle(PaymentTransactionService $payments): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        // The normal case under deferred order creation: the checkout attempt
        // never resolved, and — because no order was ever created — there's
        // nothing to cancel except the transaction record itself.
        $pending = Transaction::whereNull('order_id')
            ->where('status', Transaction::STATUS_INITIATED)
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($pending as $transaction) {
            $payments->markFailed($transaction, ['error' => 'checkout_expired'], Transaction::STATUS_CANCELED);
        }

        // Safety net: orders shouldn't be able to end up Placed/unpaid under an
        // online-gateway payment_type anymore (materialization only happens on
        // confirmed payment, or with payment_type forced to COD), but cancel
        // any that do so nothing lingers looking like a real order.
        $orders = OrderModel::whereIn('payment_type', self::GATEWAY_PAYMENT_TYPES)
            ->where('payment_status', 'unpaid')
            ->where('status', OrderModel::STATUS_PLACED)
            ->where('created_at', '<=', $cutoff)
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status' => OrderModel::STATUS_CANCELED,
                'cancel_reason' => 'Payment not completed within time limit',
            ]);

            $order->logActivity(
                action: 'auto_canceled',
                label: 'Order auto-canceled',
                description: 'Order automatically canceled after payment was not completed within '.$this->option('minutes').' minutes',
            );

            Transaction::where('order_id', $order->id)
                ->get()
                ->each(function (Transaction $transaction) use ($payments) {
                    if (! $transaction->isTerminal()) {
                        $payments->markFailed($transaction, ['error' => 'order_expired'], Transaction::STATUS_CANCELED);
                    }
                });
        }

        $this->info("Canceled {$pending->count()} pending checkout(s) and {$orders->count()} legacy unpaid order(s) older than {$cutoff}.");

        return self::SUCCESS;
    }
}
