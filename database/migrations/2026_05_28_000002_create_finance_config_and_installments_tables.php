<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('installment_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('default_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('fee_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            $table->string('academic_year');
            $table->enum('currency', ['CDF', 'USD'])->default('CDF');
            $table->decimal('amount', 12, 2);
            $table->foreignId('grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained('fee_types')->cascadeOnDelete();
            $table->foreignId('fee_rate_id')->nullable()->constrained('fee_rates')->nullOnDelete();
            $table->foreignId('installment_type_id')->nullable()->constrained('installment_types')->nullOnDelete();
            $table->string('academic_year');
            $table->enum('currency', ['CDF', 'USD'])->default('CDF');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'completed'])->default('pending');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained('payment_plans')->cascadeOnDelete();
            $table->unsignedInteger('installment_number')->default(1);
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'completed'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_plan_id')->nullable()->after('student_id')->constrained('payment_plans')->nullOnDelete();
            $table->foreignId('payment_installment_id')->nullable()->after('payment_plan_id')->constrained('payment_installments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_installment_id');
            $table->dropConstrainedForeignId('payment_plan_id');
        });

        Schema::dropIfExists('payment_installments');
        Schema::dropIfExists('payment_plans');
        Schema::dropIfExists('fee_rates');
        Schema::dropIfExists('installment_types');
        Schema::dropIfExists('fee_types');
    }
};
