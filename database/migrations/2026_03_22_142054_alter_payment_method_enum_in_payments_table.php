<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the ENUM column directly with raw SQL (supported by MySQL/MariaDB)
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('mobile_money', 'cash', 'bank_transfer', 'check') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back
        DB::statement("ALTER TABLE payments MODIFY payment_method ENUM('mobile_money', 'cash', 'bank_transfer') NOT NULL");
    }
};
