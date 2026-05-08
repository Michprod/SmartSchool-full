<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table pivot entre classes et matières avec coefficient et professeur assigné
     */
    public function up(): void
    {
        Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->integer('coefficient')->default(1); // Coefficient pour le calcul de moyenne
            $table->integer('hours_per_week')->default(2); // Heures par semaine
            $table->string('academic_year'); // Année académique (ex: 2025-2026)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Un professeur ne peut pas enseigner la même matière dans la même classe la même année
            $table->unique(['class_id', 'subject_id', 'academic_year'], 'unique_class_subject_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject');
    }
};
