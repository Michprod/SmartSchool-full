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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('currency', ['CDF', 'USD'])->default('CDF');
            $table->enum('type', ['tuition', 'registration', 'exam', 'uniform', 'transport', 'meal', 'other']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->enum('payment_method', ['mobile_money', 'cash', 'bank_transfer', 'check']);
            $table->string('mobile_money_provider')->nullable(); // mpesa, orange_money, airtel_money
            $table->string('transaction_id')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
