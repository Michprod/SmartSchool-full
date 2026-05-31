<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conduct_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('term', 3);
            $table->string('academic_year');
            $table->decimal('conduct_score', 5, 2)->nullable();
            $table->string('appreciation')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_id', 'term', 'academic_year'], 'unique_conduct_grade_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conduct_grades');
    }
};
