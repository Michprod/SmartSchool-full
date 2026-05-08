<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table pivot pour la relation many-to-many entre classes et professeurs (matières)
     */
    public function up(): void
    {
        Schema::create('class_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('subject')->nullable(); // Matière enseignée (ex: Mathématiques, Français)
            $table->string('academic_year')->nullable(); // Année académique
            $table->json('schedule')->nullable(); // Horaires spécifiques pour cette matière
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Empêcher les doublons (un professeur ne peut pas enseigner la même matière 
            // dans la même classe la même année)
            $table->unique(['class_id', 'teacher_id', 'subject', 'academic_year'], 'unique_class_teacher_subject_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_teacher');
    }
};
