<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeferOrderCreationOnTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('user_id')->nullable()->after('order_id');
            // Snapshot of the validated checkout (cart items, shipping address, recipient,
            // payment type/totals) captured at initiate time. The order row itself is only
            // created once the gateway confirms payment, using this payload — so a failed
            // or abandoned payment never leaves a phantom "Placed" order behind.
            $table->json('checkout_payload')->nullable()->after('raw_request');

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'checkout_payload']);
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }
}
