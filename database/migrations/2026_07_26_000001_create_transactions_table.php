<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('gateway'); // esewa | nicasia | khalti
            $table->string('transaction_uuid')->unique();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('status')->default('initiated'); // initiated|pending|success|failed|canceled
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NPR');
            $table->json('raw_request')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'gateway']);
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
