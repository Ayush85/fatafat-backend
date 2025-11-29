<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiKeysTable extends Migration
{
    public function up()
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('host');

            // For test/live mode, you can set keys separately
            $table->string('test_public_key')->nullable();
            $table->string('test_secret_key')->nullable();
            $table->string('live_public_key')->nullable();
            $table->string('live_secret_key')->nullable();

            // You can also store a mode column to specify whether the key is for test/live
            $table->enum('mode', ['test', 'live'])->default('test');  // 'test' or 'live'
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(0);
            $table->timestamps();

            // Set up the foreign key constraint
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');

        });
    }

    public function down()
    {
        Schema::dropIfExists('api_keys');
    }
}