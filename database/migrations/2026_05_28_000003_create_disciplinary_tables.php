<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['student', 'staff_teaching', 'staff_admin']);
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('category', ['conduct', 'administrative', 'professional'])->default('conduct');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('conduct_note')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'dismissed'])->default('open');
            $table->date('incident_date')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->enum('action_type', ['warning', 'detention', 'suspension', 'expulsion', 'administrative_note', 'other']);
            $table->text('reason')->nullable();
            $table->date('action_date');
            $table->date('end_date')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disciplinary_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->text('note');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_notes');
        Schema::dropIfExists('disciplinary_actions');
        Schema::dropIfExists('disciplinary_cases');
    }
};
