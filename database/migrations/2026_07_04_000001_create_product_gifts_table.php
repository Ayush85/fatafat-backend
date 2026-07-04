<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('gift_product_id');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('gift_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unique(['product_id', 'gift_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_gifts');
    }
};
