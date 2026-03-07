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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique();
            $table->string('student_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['M', 'F']);
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('photo')->nullable();
            
            // Contact
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            // Guardian/Parent
            $table->json('parent_ids')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_relation')->nullable();
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            
            // Academic
            $table->foreignId('class_id')->constrained('school_classes');
            $table->string('academic_year')->nullable();
            $table->string('academic_status')->nullable();
            $table->string('previous_school')->nullable();
            $table->date('enrollment_date');
            
            // Health & Medical
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->json('medical_info')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
