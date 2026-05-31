<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('staff_number')->unique();
            $table->string('staff_type', 32);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 50)->nullable();
            $table->string('avatar')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->foreignId('province_id')->nullable()->constrained('rdc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('rdc_cities')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->constrained('rdc_communes')->nullOnDelete();
            $table->string('quartier')->nullable();
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->string('job_grade')->nullable();
            $table->unsignedSmallInteger('workload_hours')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('contract_type', 64)->nullable();
            $table->string('employment_status', 32)->default('active');
            $table->text('bio')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
