<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('booking_id', 191)->nullable()->after('gateway_transaction_id');
            $table->string('correlation_id', 191)->nullable()->after('booking_id');
            $table->index('booking_id');
            $table->index('correlation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['booking_id']);
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['booking_id', 'correlation_id']);
        });
    }
};
