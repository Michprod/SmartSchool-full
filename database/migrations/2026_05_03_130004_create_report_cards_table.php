<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des bulletins trimestriels générés
     */
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            
            $table->enum('term', ['T1', 'T2', 'T3'])->default('T1');
            $table->string('academic_year');
            
            // Moyenne générale et rang
            $table->decimal('general_average', 5, 2);
            $table->integer('class_rank');
            $table->integer('total_students');
            
            // Décisions du conseil
            $table->enum('decision', [
                'pass',              // Passe en classe supérieure
                'conditional_pass',  // Passe avec conditions
                'fail',              // Redouble
                'exclude',           // Exclu
            ])->nullable();
            
            // Conseil de classe
            $table->text('class_council_observation')->nullable();
            $table->string('work_recommendations')->nullable(); // Conseils de travail
            $table->string('behavior_recommendations')->nullable(); // Conseils de conduite
            
            // Fichier PDF généré
            $table->string('pdf_path')->nullable();
            $table->boolean('is_published')->default(false); // Visible par les parents ?
            $table->timestamp('published_at')->nullable();
            
            // Génération
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('generated_at');
            
            // Validation
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->boolean('is_validated')->default(false);
            
            $table->timestamps();
            
            // Un bulletin par élève/trimestre/année
            $table->unique(['student_id', 'term', 'academic_year'], 'unique_report_card');
            
            // Index
            $table->index(['class_id', 'term', 'academic_year'], 'idx_report_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
