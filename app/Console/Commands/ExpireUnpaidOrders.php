<?php

namespace App\Console\Commands;

use App\Models\OrderModel;
use App\Models\Transaction;
use App\Services\PaymentTransactionService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid {--minutes=30 : How old an unresolved gateway order must be before it is canceled}';

    protected $description = 'Cancel Placed orders paid via an online gateway that never completed payment and were never switched to Cash on Delivery.';

    private const GATEWAY_PAYMENT_TYPES = ['esewa', 'esewa_intent', 'khalti', 'nic_asia'];

    public function handle(PaymentTransactionService $payments): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

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
                actor: null
            );

            Transaction::where('order_id', $order->id)
                ->where('gateway', '!=', null)
                ->get()
                ->each(function (Transaction $transaction) use ($payments) {
                    if (! $transaction->isTerminal()) {
                        $payments->markFailed($transaction, ['error' => 'order_expired'], Transaction::STATUS_CANCELED);
                    }
                });
        }

        $this->info("Canceled {$orders->count()} unpaid order(s) older than {$cutoff}.");

        return self::SUCCESS;
    }
}
