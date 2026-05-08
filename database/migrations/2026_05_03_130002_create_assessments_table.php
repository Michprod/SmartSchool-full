<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des évaluations/notes (devoirs, contrôles, compositions)
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            
            // Type d'évaluation
            $table->enum('type', [
                'interrogation',   // Interrogation courte
                'devoir',          // Devoir sur table
                'composition',     // Composition/Exam
                'examen',          // Examen final
                'projet',          // Projet
                'participation',   // Participation
            ])->default('devoir');
            
            // Période d'évaluation
            $table->enum('term', ['T1', 'T2', 'T3'])->default('T1'); // Trimestre 1, 2, 3
            $table->string('academic_year'); // Année académique
            
            // Notes
            $table->decimal('score', 5, 2); // Note obtenue (ex: 15.50)
            $table->decimal('max_score', 5, 2)->default(20); // Note maximale (20, 10, etc.)
            $table->decimal('coefficient', 3, 1)->default(1); // Coefficient du devoir
            
            // Détails
            $table->string('title')->nullable(); // Titre du devoir (ex: "Devoir #3 - Équations")
            $table->text('comment')->nullable(); // Commentaire/observation du professeur
            $table->date('date'); // Date de l'évaluation
            $table->boolean('is_published')->default(false); // Visible par l'élève/parent ?
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['student_id', 'subject_id', 'term', 'academic_year'], 'idx_assessment_calculation');
            $table->index(['teacher_id', 'class_id'], 'idx_teacher_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
