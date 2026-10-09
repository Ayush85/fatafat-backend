<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrandfatherNameToEmiTables extends Migration
{
    public function up()
    {
        foreach (['emi_requests', 'emi_request_guarantors'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'grandfather_name')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('grandfather_name', 191)->nullable()->after('name');
                });
            }
        }
    }

    public function down()
    {
        foreach (['emi_requests', 'emi_request_guarantors'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'grandfather_name')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('grandfather_name');
                });
            }
        }
    }
}
