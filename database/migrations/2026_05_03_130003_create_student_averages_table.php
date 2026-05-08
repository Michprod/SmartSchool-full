<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des moyennes calculées par élève/matière/trimestre
     */
    public function up(): void
    {
        Schema::create('student_averages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            
            $table->enum('term', ['T1', 'T2', 'T3'])->default('T1');
            $table->string('academic_year');
            
            // Moyenne calculée
            $table->decimal('average_score', 5, 2); // Moyenne sur 20
            $table->integer('total_coefficient')->default(0); // Somme des coefficients
            $table->integer('assessments_count')->default(0); // Nombre d'évaluations
            
            // Moyenne générale de l'élève sur le trimestre (toutes matières)
            $table->decimal('general_average', 5, 2)->nullable();
            
            // Rang dans la classe
            $table->integer('class_rank')->nullable(); // Position
            $table->integer('total_students')->nullable(); // Total d'élèves
            
            // Observations
            $table->string('appreciation')->nullable(); // Appréciation automatique
            $table->text('teacher_comment')->nullable(); // Commentaire du titulaire
            
            $table->timestamp('calculated_at'); // Date du calcul
            $table->timestamps();
            
            // Un seul enregistrement par élève/matière/trimestre/année
            $table->unique(['student_id', 'subject_id', 'term', 'academic_year'], 'unique_student_subject_term');
            
            // Index
            $table->index(['class_id', 'term', 'academic_year', 'general_average'], 'idx_class_ranking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_averages');
    }
};
