<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPreOrderToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_pre_order')) {
                $table->boolean('is_pre_order')->default(false)->after('payment_type');
            }
            if (! Schema::hasColumn('orders', 'deposit_amount')) {
                // Amount actually collected now to secure a pre-order, when it differs
                // from the full order_total (e.g. a capped deposit toward the item price).
                $table->decimal('deposit_amount', 10, 2)->nullable()->after('is_pre_order');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'deposit_amount')) {
                $table->dropColumn('deposit_amount');
            }
            if (Schema::hasColumn('orders', 'is_pre_order')) {
                $table->dropColumn('is_pre_order');
            }
        });
    }
}
