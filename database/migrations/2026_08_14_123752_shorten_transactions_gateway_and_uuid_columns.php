<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // doctrine/dbal isn't installed, so column length changes go through raw SQL
        // instead of Blueprint::change().
        DB::statement('ALTER TABLE transactions MODIFY gateway VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE transactions MODIFY transaction_uuid VARCHAR(36) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE transactions MODIFY gateway VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE transactions MODIFY transaction_uuid VARCHAR(255) NOT NULL');
    }
};
